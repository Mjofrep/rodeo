<?php
declare(strict_types=1);
require __DIR__ . '/app.php';

try {
    $pdo = db();
    $action = $_GET['action'] ?? '';
    $competitions = competitions();

    if ($action !== 'board') {
        $authenticatedJudgeId = requireAuthenticatedJudge();
    }

    if ($action === 'board') {
        $rows = $pdo->query('SELECT * FROM rodeo')->fetchAll();
        $board = [];
        foreach (teams() as $teamId => $team) {
            $entry = ['team' => $team, 'competitions' => [], 'total' => 0, 'complete' => 0, 'suspended' => false];
            foreach ($competitions as $competitionId => $competition) {
                $scores = [];
                $completed = 0;
                $status = 'pending';
                foreach ($rows as $row) {
                    if ((int) $row['team_id'] === $teamId && (int) $row['competition_id'] === $competitionId) {
                        $ratings = json_decode($row['scores'], true) ?: [];
                        $scores[] = score($competition['criteria'], $ratings) - (float) $row['deductions'];
                        if ($row['status'] === 'suspended') $status = 'suspended';
                        if ($row['status'] === 'completed') {
                            $entry['complete']++;
                            $completed++;
                        }
                    }
                }
                $average = count($scores) ? round(array_sum($scores) / count($scores), 2) : null;
                $entry['competitions'][$competitionId] = ['score' => $average, 'count' => count($scores), 'completed' => $completed, 'status' => $status];
                $entry['total'] += $average ?? 0;
                $entry['suspended'] = $entry['suspended'] || $status === 'suspended';
            }
            $board[] = $entry;
        }
        usort($board, fn($a, $b) => $b['total'] <=> $a['total']);
        jsonResponse(['board' => $board, 'updatedAt' => date('H:i')]);
    }

    if ($action === 'progress') {
        $judgeId = $authenticatedJudgeId;

        $progress = [];
        foreach ($competitions as $competitionId => $_competition) {
            foreach (teams() as $teamId => $_team) {
                $progress[$competitionId][$teamId] = 'pending';
            }
        }

        $statement = $pdo->prepare('SELECT team_id, competition_id, status FROM rodeo WHERE judge_id = ?');
        $statement->execute([$judgeId]);
        foreach ($statement->fetchAll() as $row) {
            $competitionId = (int) $row['competition_id'];
            $teamId = (int) $row['team_id'];
            if (isset($progress[$competitionId][$teamId])) {
                $progress[$competitionId][$teamId] = $row['status'];
            }
        }

        $summary = [];
        foreach ($progress as $competitionId => $teamsProgress) {
            $terminal = 0;
            foreach ($teamsProgress as $status) {
                if (in_array($status, ['completed', 'suspended'], true)) {
                    $terminal++;
                }
            }
            $summary[$competitionId] = ['terminal' => $terminal, 'finished' => $terminal === count($teamsProgress)];
        }
        jsonResponse(['progress' => $progress, 'summary' => $summary]);
    }

    if ($action === 'evaluation') {
        $judgeId = $authenticatedJudgeId;
        $teamId = (int) ($_GET['team'] ?? 0);
        $competitionId = (int) ($_GET['competition'] ?? 0);
        $judge = judges()[$judgeId] ?? null;
        if (!$judge || !isset(teams()[$teamId]) || !isset($competitions[$competitionId])) jsonResponse(['error' => 'Solicitud inválida.'], 422);
        $statement = $pdo->prepare('SELECT * FROM rodeo WHERE judge_id = ? AND team_id = ? AND competition_id = ?');
        $statement->execute([$judgeId, $teamId, $competitionId]);
        $row = $statement->fetch() ?: null;
        jsonResponse(['evaluation' => $row ? ['ratings' => json_decode($row['scores'], true), 'deductions' => $row['deductions'], 'observations' => $row['observations'], 'status' => $row['status']] : null]);
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $judgeId = $authenticatedJudgeId;
        $teamId = (int) ($input['team'] ?? 0);
        $competitionId = (int) ($input['competition'] ?? 0);
        $judge = judges()[$judgeId] ?? null;
        $ratings = $input['ratings'] ?? [];
        if (!$judge || !isset(teams()[$teamId]) || !isset($competitions[$competitionId]) || !is_array($ratings)) jsonResponse(['error' => 'Datos inválidos.'], 422);
        $existing = $pdo->prepare('SELECT status FROM rodeo WHERE judge_id = ? AND team_id = ? AND competition_id = ?');
        $existing->execute([$judgeId, $teamId, $competitionId]);
        $existingStatus = $existing->fetchColumn();
        if (in_array($existingStatus, ['completed', 'suspended'], true)) {
            jsonResponse(['error' => 'Esta evaluación ya fue finalizada y no puede modificarse.'], 409);
        }
        $competition = $competitions[$competitionId];
        $valid = ['S', 'C', 'ED', 'NC'];
        foreach ($ratings as $index => $rating) {
            if (!array_key_exists((int) $index, $competition['criteria']) || !in_array($rating, $valid, true)) {
                jsonResponse(['error' => 'Calificación inválida.'], 422);
            }
        }
        if (($input['complete'] ?? false) && count($ratings) !== count($competition['criteria'])) {
            jsonResponse(['error' => 'Califique todos los indicadores antes de cerrar.'], 422);
        }
        $status = hasCriticalNc($competition['criteria'], $ratings) ? 'suspended' : (($input['complete'] ?? false) ? 'completed' : 'draft');
        $deductions = max(0, (float) ($input['deductions'] ?? 0));
        $observations = trim((string) ($input['observations'] ?? ''));
        $statement = $pdo->prepare('INSERT INTO rodeo (judge_id, team_id, competition_id, status, scores, deductions, observations, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, IF(? = "completed", NOW(), NULL)) ON DUPLICATE KEY UPDATE status = VALUES(status), scores = VALUES(scores), deductions = VALUES(deductions), observations = VALUES(observations), completed_at = IF(VALUES(status) = "completed", NOW(), NULL)');
        $statement->execute([$judgeId, $teamId, $competitionId, $status, json_encode($ratings), $deductions, $observations, $status]);
        jsonResponse(['ok' => true, 'status' => $status, 'score' => score($competition['criteria'], $ratings) - $deductions]);
    }

    jsonResponse(['error' => 'Acción no encontrada.'], 404);
} catch (Throwable $exception) {
    jsonResponse(['error' => 'No fue posible conectar con MySQL. Verifique MAMP y las credenciales configuradas.', 'detail' => $exception->getMessage()], 500);
}
