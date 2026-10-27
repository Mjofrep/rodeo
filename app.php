<?php
declare(strict_types=1);

// Production MySQL configuration. The database is provisioned by the hosting account.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'noeticac_rodeo';
const DB_USER = 'noeticac_root';
const DB_PASS = 'Turing_1964$';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS jueces (
            id TINYINT UNSIGNED PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            nombre_completo VARCHAR(150) NOT NULL,
            password_hash VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS rodeo (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            judge_id TINYINT UNSIGNED NOT NULL,
            team_id TINYINT UNSIGNED NOT NULL,
            competition_id TINYINT UNSIGNED NOT NULL,
            status ENUM("draft", "suspended", "completed") NOT NULL DEFAULT "draft",
            scores JSON NOT NULL,
            deductions DECIMAL(6,2) NOT NULL DEFAULT 0,
            observations TEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY unique_evaluation (judge_id, team_id, competition_id)
        ) ENGINE=InnoDB'
    );

    $seedJudges = [
        [1, 'J01', 'Juez 01', '1001'],
        [2, 'J02', 'Juez 02', '1002'],
        [3, 'J03', 'Juez 03', '1003'],
        [4, 'J04', 'Juez 04', '1004'],
    ];
    $statement = $pdo->prepare('INSERT IGNORE INTO jueces (id, codigo, nombre_completo, password_hash) VALUES (?, ?, ?, ?)');
    foreach ($seedJudges as [$id, $code, $name, $password]) {
        $statement->execute([$id, $code, $name, password_hash($password, PASSWORD_DEFAULT)]);
    }
    return $pdo;
}

function teams(): array
{
    return [
        1 => ['name' => 'Cuadrilla Norte', 'company' => 'Dominion', 'logo' => 'Dominion.jpg', 'mark' => '01', 'tone' => 'lime'],
        2 => ['name' => 'Cuadrilla Centro', 'company' => 'INMEL', 'logo' => 'INMEL.jpg', 'mark' => '02', 'tone' => 'orange'],
        3 => ['name' => 'Cuadrilla Costa', 'company' => 'Grupo Lari', 'logo' => 'Lari.jpg', 'mark' => '03', 'tone' => 'blue'],
        4 => ['name' => 'Cuadrilla Sur', 'company' => 'BlueTeam', 'logo' => 'ceonext.png', 'mark' => '04', 'tone' => 'red'],
    ];
}

function judges(): array
{
    static $judges;
    if ($judges !== null) {
        return $judges;
    }

    $judges = [];
    foreach (db()->query('SELECT id, codigo, nombre_completo FROM jueces ORDER BY id')->fetchAll() as $judge) {
        $judges[(int) $judge['id']] = ['name' => $judge['nombre_completo'], 'code' => $judge['codigo']];
    }
    return $judges;
}

function startJudgeSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function authenticatedJudge(): ?array
{
    startJudgeSession();
    $judgeId = (int) ($_SESSION['judge_id'] ?? 0);
    return judges()[$judgeId] ?? null;
}

function authenticateJudge(string $code, string $password): ?int
{
    $statement = db()->prepare('SELECT id, password_hash FROM jueces WHERE codigo = ?');
    $statement->execute([trim($code)]);
    $judge = $statement->fetch();
    if (!$judge || !password_verify($password, $judge['password_hash'])) {
        return null;
    }
    return (int) $judge['id'];
}

function requireAuthenticatedJudge(): int
{
    $judge = authenticatedJudge();
    if ($judge === null) {
        jsonResponse(['error' => 'Debe iniciar sesión para continuar.'], 401);
    }
    return (int) $_SESSION['judge_id'];
}

function competitions(): array
{
    return [
        1 => [
            'short' => 'Cruceta',
            'title' => 'Cambio de cruceta de paso con capacho',
            'subtitle' => 'Trabajo desenergizado',
            'criteria' => [
                [6, false, 'Camión hidroelevador correctamente aterrizado en la zona de trabajo.'],
                [6, true, 'Liniero con todo el EPP antes de iniciar el ascenso.'],
                [5, false, 'Liniero mantiene los guantes durante todo el proceso.'],
                [5, true, 'Ascenso seguro al capacho y aproximación a la red desenergizada.'],
                [6, false, 'Revisión de claros de línea y amarras antes de las protecciones.'],
                [6, true, 'Cubre-línea instalado correctamente en ambas direcciones del poste.'],
                [5, false, 'Posición segura respecto de conductores y elementos a tierra.'],
                [6, false, 'Líneas y espigas protegidas antes de instalar la cruceta auxiliar.'],
                [6, false, 'Mástil y brazo auxiliar instalados después de las mantas partidas.'],
                [6, false, 'Manta partida bajo cada aislador antes de soltar amarras.'],
                [7, false, 'Traslado seguro de líneas a la cruceta auxiliar en orden de fases.'],
                [8, true, 'Cambio de cruceta con todas las líneas en su lugar.'],
                [8, false, 'Retorno de líneas a la nueva cruceta con cada fase protegida.'],
                [5, false, 'Manta entera protege la cabeza del poste durante línea central.'],
                [5, false, 'Desmontaje de brazo auxiliar y mástil con líneas aseguradas.'],
                [5, false, 'Retiro de mantas y cubre-líneas en secuencia correcta.'],
                [5, true, 'Aviso a Despacho y cancelación del permiso preventivo.'],
            ],
        ],
        2 => [
            'short' => 'Poste + PPF',
            'title' => 'Instalación de postes MT con PPF',
            'subtitle' => 'Armado completo e instalación de PPF',
            'criteria' => [
                [6, false, 'Analiza riesgos, define controles y distribuye funciones.'],
                [6, true, 'Delimita faena y controla accesos, tránsito y zona de izaje.'],
                [8, true, 'EPP adecuado y autorización del personal verificados.'],
                [8, true, 'Autorización y condición eléctrica segura confirmadas.'],
                [6, false, 'Poste, herrajes, PPF, herramientas y accesorios revisados.'],
                [8, false, 'Crucetas, aisladores y herrajes montados según armado definido.'],
                [10, true, 'Apoyo de camión, estabilizadores y sujeciones verificados.'],
                [10, true, 'Comunicación y movimiento de carga controlados.'],
                [6, false, 'Excavación verificada según diseño y controles.'],
                [8, true, 'Poste posicionado y asegurado antes de liberar sujeciones.'],
                [6, false, 'Orientación, aplome, empotramiento y compactación comprobados.'],
                [6, true, 'PPF instalado y verificado según diseño aprobado.'],
                [6, false, 'Armado, ajustes y PPF revisados antes de entregar.'],
                [6, false, 'Residuos y herramientas retirados; área segura al término.'],
            ],
        ],
    ];
}

function score(array $criteria, array $ratings): float
{
    $scale = ['S' => 1, 'C' => .75, 'ED' => .5, 'NC' => 0];
    $total = 0;
    foreach ($criteria as $index => $criterion) {
        $total += $criterion[0] * ($scale[$ratings[$index] ?? 'NC'] ?? 0);
    }
    return round($total, 2);
}

function hasCriticalNc(array $criteria, array $ratings): bool
{
    foreach ($criteria as $index => $criterion) {
        if ($criterion[1] && ($ratings[$index] ?? null) === 'NC') {
            return true;
        }
    }
    return false;
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
