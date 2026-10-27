<?php
declare(strict_types=1);
require __DIR__ . '/app.php';
$judgeArea = ($_GET['vista'] ?? '') === 'jueces';
$loginError = '';
if ($judgeArea) {
    startJudgeSession();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
        $judgeId = authenticateJudge((string) ($_POST['codigo'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($judgeId !== null) {
            session_regenerate_id(true);
            $_SESSION['judge_id'] = $judgeId;
            header('Location: ' . ($_SERVER['PHP_SELF'] ?? 'evaluar.php'));
            exit;
        }
        $loginError = 'Código o contraseña incorrectos.';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . ($_SERVER['PHP_SELF'] ?? 'evaluar.php'));
        exit;
    }
}
$competitions = competitions();
$judgeId = $judgeArea ? (int) ($_SESSION['judge_id'] ?? 0) : 0;
$judge = $judgeArea ? authenticatedJudge() : null;
$competitionId = (int) ($_GET['actividad'] ?? 0);
$competition = $competitions[$competitionId] ?? null;
$judgeSelected = $judge !== null;
$judgeMode = $judgeSelected && $competition !== null;
$boardMode = !$judgeSelected && ($_GET['vista'] ?? '') !== 'jueces';
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$apiUrl = ($publicPath ?: '') . '/api.php';
$sponsorsUrl = ($publicPath ?: '') . '/auspiciadores.png';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ENEL | Torneo MT</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap');
    :root{--ink:#071a22;--paper:#f2f0e8;--acid:#d7ff36;--orange:#ff6b35;--blue:#27b6e8;--red:#f34b55;--muted:#779097;--line:rgba(7,26,34,.14)}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Manrope,sans-serif}header{min-height:138px;background:var(--ink);color:white;display:flex;align-items:center;justify-content:space-between;padding:27px max(5vw,28px);position:relative;overflow:hidden}header:after{content:"";position:absolute;width:460px;height:460px;border:1px solid rgba(215,255,54,.26);border-radius:50%;right:-130px;top:-326px;box-shadow:0 0 0 48px rgba(215,255,54,.05),0 0 0 96px rgba(215,255,54,.03)}.brand{font-family:'Barlow Condensed';font-size:50px;font-weight:800;letter-spacing:-2px;line-height:.75;color:var(--acid);z-index:1}.brand small{font:500 10px 'DM Mono';letter-spacing:1.8px;color:#fff;display:block;margin:14px 0 0 3px}.event{text-align:right;z-index:1}.event strong{display:block;font:700 29px 'Barlow Condensed';letter-spacing:.5px}.event span{font:500 11px 'DM Mono';color:var(--acid);letter-spacing:1px}.nav{display:flex;gap:10px;padding:18px max(5vw,28px);border-bottom:1px solid var(--line)}.button{border:1px solid var(--ink);padding:10px 14px;background:transparent;color:var(--ink);font:700 12px Manrope;text-decoration:none;cursor:pointer}.button.primary{background:var(--ink);color:var(--acid)}main{max-width:1440px;margin:auto;padding:42px max(5vw,28px) 70px}.eyebrow{font:500 11px 'DM Mono';letter-spacing:1.35px;color:#4c6a72;text-transform:uppercase}.title-row{display:flex;justify-content:space-between;align-items:end;border-bottom:2px solid var(--ink);padding-bottom:18px;margin-bottom:21px;gap:20px}h1{font:800 clamp(38px,7vw,78px)/.82 'Barlow Condensed';letter-spacing:-1.5px;text-transform:uppercase;margin:8px 0 0}.live{font:500 11px 'DM Mono';white-space:nowrap}.live i{display:inline-block;width:8px;height:8px;background:var(--orange);border-radius:50%;margin-right:7px;animation:pulse 1.4s infinite}@keyframes pulse{50%{opacity:.25}}.score-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.team-card{background:#fff;min-height:305px;border-top:7px solid var(--ink);padding:20px;display:flex;flex-direction:column;position:relative;overflow:hidden}.team-card[data-tone="lime"]{border-color:#a0bd00}.team-card[data-tone="orange"]{border-color:var(--orange)}.team-card[data-tone="blue"]{border-color:var(--blue)}.team-card[data-tone="red"]{border-color:var(--red)}.rank{font:700 14px 'DM Mono';color:var(--muted)}.company{font:700 13px Manrope;margin-top:24px}.team-name{font:800 37px/.86 'Barlow Condensed';text-transform:uppercase;margin:5px 0 18px;max-width:180px}.mark{position:absolute;right:16px;top:9px;font:800 76px/.8 'Barlow Condensed';letter-spacing:-5px;color:#edf0e8}.total{margin-top:auto;border-top:1px solid var(--line);padding-top:14px}.total b{font:800 60px/.75 'Barlow Condensed';letter-spacing:-2px}.total span{font:500 10px 'DM Mono';color:var(--muted)}.splits{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}.split{border:1px solid var(--line);padding:8px;font:500 10px 'DM Mono'}.split b{display:block;font:800 24px 'Barlow Condensed';margin-top:2px}.status{font:500 9px 'DM Mono';text-transform:uppercase;color:#658008}.status.suspended{color:#c2222b}.legend{display:flex;gap:18px;margin-top:25px;font:500 10px 'DM Mono';color:#4c6a72}.legend i{display:inline-block;width:8px;height:8px;margin-right:5px}.warning{background:#ffd9d3;border-left:5px solid var(--red);padding:15px 18px;margin:0 0 20px;font-size:13px;display:none}.judge-shell{max-width:980px}.judge-header{display:flex;justify-content:space-between;align-items:end;margin-bottom:25px}.team-tabs{display:flex;gap:7px;flex-wrap:wrap;margin:20px 0}.team-tabs button{background:white;border:1px solid var(--line);padding:12px 15px;font:700 13px Manrope;cursor:pointer}.team-tabs button.active{background:var(--ink);color:var(--acid);border-color:var(--ink)}.rubric{background:#fff;border-top:7px solid var(--acid);padding:26px}.rubric-head{display:flex;justify-content:space-between;gap:20px;border-bottom:1px solid var(--line);padding-bottom:20px;margin-bottom:4px}.rubric h2{font:800 35px/.9 'Barlow Condensed';margin:5px 0}.running-score{font:800 48px/.8 'Barlow Condensed';text-align:right}.criterion{display:grid;grid-template-columns:44px 1fr 55px 195px;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--line)}.number{font:500 11px 'DM Mono';color:var(--muted)}.criterion p{font-size:12px;line-height:1.38;margin:0}.points{font:700 18px 'Barlow Condensed';text-align:right}.rating{display:grid;grid-template-columns:repeat(4,1fr);gap:3px}.rating button{height:31px;border:1px solid #cbd3d0;background:#fff;font:700 11px 'DM Mono';cursor:pointer}.rating button.active{background:var(--ink);color:var(--acid);border-color:var(--ink)}.rating button.nc.active{background:var(--red);color:#fff;border-color:var(--red)}.notes{margin-top:24px;display:grid;grid-template-columns:150px 1fr;gap:12px}.notes input,.notes textarea{width:100%;border:1px solid var(--line);font:500 13px Manrope;padding:10px;background:#fbfaf5}.notes textarea{height:80px;resize:vertical}.actions{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-top:22px}.save-status{font:500 11px 'DM Mono';color:#4c6a72}.error{background:#ffd9d3;padding:16px;margin:30px 0}.login-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:25px}.judge-link{display:block;text-decoration:none;color:var(--ink);background:white;border-top:5px solid var(--ink);padding:20px;min-height:156px}.judge-link b{display:block;font:800 30px 'Barlow Condensed';margin-top:15px}.judge-link span{font:500 10px 'DM Mono';color:var(--muted)}@media(max-width:900px){.score-grid,.login-grid{grid-template-columns:repeat(2,1fr)}.criterion{grid-template-columns:32px 1fr 45px}.rating{grid-column:2/5}.notes{grid-template-columns:1fr}.rubric{padding:17px}}@media(max-width:520px){header{min-height:110px;padding:21px}.brand{font-size:42px}.event strong{font-size:21px}.event span{font-size:9px}.nav,main{padding-left:18px;padding-right:18px}.score-grid,.login-grid{grid-template-columns:1fr}.title-row{align-items:start;flex-direction:column}.criterion{gap:8px}.criterion p{font-size:11px}.rating{grid-column:1/5}.actions{align-items:start;flex-direction:column}.rubric-head{align-items:start;flex-direction:column}.running-score{text-align:left}}
    /* CEO light interface overrides */
    :root{--ink:#0f172a;--paper:#fff;--blue:#0d6efd;--blue-deep:#084298;--blue-soft:#eef4ff;--line:#dbe5f2;--muted:#64748b;--acid:#0d6efd;--orange:#f59e0b;--red:#dc3545}
    body{background:#fff;color:var(--ink);font-family:Manrope,sans-serif}
    header{min-height:100px;background:linear-gradient(90deg,#f8fbff 0%,#fff 72%);color:var(--ink);padding:18px max(5vw,28px);border-bottom:1px solid var(--line);overflow:visible}
    header:after{display:none}
    .brand{display:flex;align-items:center;color:var(--ink);font-size:0;letter-spacing:0}
    .brand:before{content:"";display:block;width:156px;height:58px;background:url('ceonext.png') left center/contain no-repeat}
    .brand small{font:600 10px 'DM Mono';color:var(--muted);letter-spacing:1.35px;margin:0 0 0 18px;max-width:195px;line-height:1.45}
    .event strong{font-family:Manrope,sans-serif;font-size:17px;letter-spacing:-.35px;color:var(--ink)}
    .event span{color:var(--blue);font:600 10px 'DM Mono';letter-spacing:.85px}
    .nav{background:#fff;border-bottom:1px solid var(--line);padding:12px max(5vw,28px);gap:8px}
    .button{border:1px solid #b8c9df;border-radius:7px;padding:9px 14px;color:#334155;font-size:12px;transition:.18s ease}
    .button:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}
    .button.primary{background:var(--blue);border-color:var(--blue);color:#fff}
    main{max-width:1280px;padding-top:46px}
    .eyebrow{color:var(--blue);font-weight:600}
    .title-row{border-bottom:1px solid var(--line);padding-bottom:22px}
    h1{font-family:Manrope,sans-serif;font-size:clamp(31px,5vw,56px);line-height:1;letter-spacing:-2.5px;text-transform:none}
    .live{color:var(--muted)}.live i{background:var(--blue)}
    .login-grid{gap:16px}.judge-link{background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 6px 18px rgba(15,23,42,.05);transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}.judge-link:hover{transform:translateY(-3px);border-color:#8eb7ff;box-shadow:0 12px 24px rgba(13,110,253,.12)}.judge-link b{color:var(--ink)}
    .score-grid{gap:16px}.team-card{background:#fff;border:1.1px solid var(--line);border-radius:10px;box-shadow:0 5px 16px rgba(15,23,42,.04);overflow:hidden}.team-card:before{background:var(--blue)}.rank,.mark{color:var(--blue)}.company{color:var(--muted)}.team-name{color:var(--ink)}.total{background:#f8fbff;border-top:1.1px solid var(--line)}.total span,.split{color:var(--muted)}.total b{color:var(--blue-deep)}.status{background:#eaf3ff;color:var(--blue-deep)}.status.suspended{background:#fff0f1;color:#b4232d}.legend{border-top:1px solid var(--line);color:var(--muted)}
    .judge-header{border-bottom:1px solid var(--line)}.team-tabs{border-bottom:1px solid var(--line)}.team-tabs button{color:#475569;border-color:var(--line);background:#fff;border-radius:7px}.team-tabs button.active{background:var(--blue);color:#fff;border-color:var(--blue)}.rubric{background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 5px 18px rgba(15,23,42,.04);overflow:hidden}.rubric-head{background:#f8fbff;border-bottom:1px solid var(--line)}.rubric h2{color:var(--ink)}.running-score{color:var(--blue-deep)}.criterion{border-color:var(--line)}.criterion:hover{background:#f8fbff}.number{color:var(--blue)}.criterion p{color:#334155}.points{color:var(--muted)}.rating button{border-radius:6px;border-color:#cbd9ea;color:#475569}.rating button.active{background:var(--blue);border-color:var(--blue);color:#fff}.notes{background:#f8fbff;border-top:1px solid var(--line)}.notes label{color:#334155}.notes input,.notes textarea{border-color:#cbd9ea;background:#fff;border-radius:6px}.notes input:focus,.notes textarea:focus{border-color:var(--blue);outline:2px solid rgba(13,110,253,.12)}.warning{background:#fff6e9;border-color:#f4c571;color:#8a4b00}.save-status{color:var(--muted)}.error{color:#b4232d;background:#fff0f1;border-color:#f5c2c7}
    .company:before{content:"";display:block;width:150px;height:58px;margin:13px 0 6px;background:left center/contain no-repeat}.team-card[data-tone="lime"] .company:before{background-image:url('Dominion.jpg')}.team-card[data-tone="orange"] .company:before{background-image:url('INMEL.jpg')}.team-card[data-tone="blue"] .company:before{background-image:url('Lari.jpg')}.team-card[data-tone="red"] .company:before{background-image:url('ceonext.png')}.team-name{display:none}.team-tabs button{display:inline-flex;align-items:center;gap:8px}.team-tabs button:before{content:"";display:block;width:70px;height:26px;background:center/contain no-repeat}.team-tabs button:nth-child(1):before{background-image:url('Dominion.jpg')}.team-tabs button:nth-child(2):before{background-image:url('INMEL.jpg')}.team-tabs button:nth-child(3):before{background-image:url('Lari.jpg')}.team-tabs button:nth-child(4):before{background-image:url('ceonext.png')}
    .board-page{background:#eef4f9 url('trabajadores.png') center bottom/cover fixed no-repeat}.board-page header{background:#fff}.board-page main{min-height:calc(100vh - 100px);position:relative}.board-page main>*{position:relative;z-index:1}.board-page .title-row{background:rgba(255,255,255,.82);backdrop-filter:blur(4px);padding:16px 18px 22px;border-radius:10px;border:1px solid rgba(255,255,255,.65)}.title-copy{position:relative}.safety-badge{position:absolute;left:calc(100% + 38px);top:50%;transform:translateY(-50%);width:170px;padding:11px 14px;background:#dc3545;color:#fff;border-radius:6px;font:700 12px/1.25 Manrope;text-align:center;box-shadow:0 5px 12px rgba(180,35,48,.2)}.mt-red{color:#dc3545}
    .sponsors{width:min(100%,1100px);height:clamp(105px,12vw,150px);margin:16px auto 0;overflow:hidden}.sponsors img{display:block;width:100%;height:auto;transform:translateY(-31%)}
    @media(min-width:1000px){.board-page main{max-width:1520px;padding-left:max(3vw,28px);padding-right:max(3vw,28px)}.board-page .score-grid{grid-template-columns:repeat(4,minmax(280px,1fr))}.board-page .team-card{min-height:455px}.board-page .company:before{width:180px;height:72px}.board-page .rank,.board-page .mark,.board-page .company{font-size:1.1em}.board-page .total b{font-size:1.16em}.board-page .split{font-size:1.05em}}
    .board-page .total{padding:22px 22px 24px}.board-page .total>span{display:block;font:700 11px 'DM Mono';letter-spacing:1px;color:#64748b}.board-page .total>b{display:block;margin:7px 0 18px;font:800 clamp(64px,7.2vw,108px)/.78 'Barlow Condensed';letter-spacing:-3px;color:var(--blue-deep);font-variant-numeric:tabular-nums}.board-page .split{display:grid;grid-template-columns:1fr auto;align-items:end;gap:5px 10px;padding:14px 0;border-top:1px solid var(--line);font:700 12px Manrope;letter-spacing:.15px}.board-page .split b{grid-column:2;font:800 clamp(25px,2.3vw,36px)/.95 'Barlow Condensed';letter-spacing:-.7px;color:var(--ink);font-variant-numeric:tabular-nums}.board-page .split .status{grid-column:1/-1;justify-self:start;margin-top:2px;font-size:10px;letter-spacing:.7px}
    .team-tabs button:disabled{cursor:not-allowed;opacity:.6}.rating button.active:disabled{background:var(--blue);border-color:var(--blue);color:#fff;opacity:1}.judge-link.is-finished{opacity:.6}
    .login-form{width:min(100%,430px);margin-top:28px;padding:28px;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.07)}.login-form label{display:block;margin:0 0 18px;color:#334155;font:700 12px Manrope}.login-form input{display:block;width:100%;margin-top:7px;padding:12px;border:1px solid #cbd9ea;border-radius:6px;font:500 15px Manrope}.login-form input:focus{border-color:var(--blue);outline:2px solid rgba(13,110,253,.12)}.login-form .button{width:100%;margin-top:4px}.login-form .error{margin:0 0 18px}
    @media(max-width:620px){header{align-items:flex-start;gap:15px;flex-direction:column}.event{text-align:left}.brand:before{width:132px;height:49px}.brand small{margin-left:13px;font-size:9px}main{padding-top:30px}.team-tabs button img{width:58px}.board-page main{min-height:calc(100vh - 100px)}.safety-badge{position:static;transform:none;margin-top:16px}}
  </style>
</head>
<body class="<?= $boardMode ? 'board-page' : '' ?>">
<header><div class="brand"><small>TORNEO INFRAESTRUCTURA AÉREA MT</small></div></header>
<?php if (!$judgeSelected && ($_GET['vista'] ?? '') === 'jueces'): ?>
<main><span class="eyebrow">Acceso de evaluación</span><h1>Ingreso de<br>jueces</h1><form class="login-form" method="post"><input type="hidden" name="action" value="login"><?php if ($loginError): ?><div class="error"><?= htmlspecialchars($loginError) ?></div><?php endif ?><label>Código<input name="codigo" autocomplete="username" required autofocus></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button class="button primary" type="submit">Ingresar</button></form></main>
<?php elseif ($judgeSelected && !$competition): ?>
<main><span class="eyebrow"><?= htmlspecialchars($judge['name']) ?> · Paso 02</span><h1>Selecciona<br>la prueba</h1><div class="login-grid"><?php foreach ($competitions as $id => $item): ?><a class="judge-link" data-competition="<?= $id ?>" href="?actividad=<?= $id ?>"><span>Prueba 0<?= $id ?></span><b><?= htmlspecialchars($item['short']) ?></b><span><?= htmlspecialchars($item['title']) ?></span><span class="evaluation-progress">Cargando estado...</span></a><?php endforeach ?></div><div class="actions"><form method="post"><input type="hidden" name="action" value="logout"><button class="button" type="submit">Cerrar sesión</button></form></div></main>
<?php elseif ($judgeMode): ?>
<main class="judge-shell"><div class="judge-header"><div><span class="eyebrow"><?= htmlspecialchars($judge['name']) ?> · Actividad <?= $competitionId ?></span><h1><?= htmlspecialchars($competition['short']) ?></h1></div><a class="button" href="?vista=jueces">Cambiar juez</a></div><div id="critical-warning" class="warning"><strong>PRUEBA DETENIDA.</strong> Un criterio crítico fue calificado como NC. Registre evidencia y active la revisión de seguridad; no cierre esta pauta.</div><div class="team-tabs" id="team-tabs"><?php foreach (teams() as $id => $team): ?><button data-team="<?= $id ?>"><?= htmlspecialchars($team['company']) ?></button><?php endforeach ?></div><section class="rubric"><div class="rubric-head"><div><span class="eyebrow" id="selected-team">Selecciona una cuadrilla</span><h2><?= htmlspecialchars($competition['title']) ?></h2><span class="eyebrow"><?= htmlspecialchars($competition['subtitle']) ?> · * CRÍTICO</span></div><div class="running-score"><span id="score">0</span><small>/100</small></div></div><div id="criteria"><?php foreach ($competition['criteria'] as $index => [$points, $critical, $text]): ?><div class="criterion" data-index="<?= $index ?>" data-points="<?= $points ?>" data-critical="<?= $critical ? '1' : '0' ?>"><div class="number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?><?= $critical ? '*' : '' ?></div><p><?= htmlspecialchars($text) ?></p><div class="points"><?= $points ?></div><div class="rating"><?php foreach (['S', 'C', 'ED', 'NC'] as $rating): ?><button class="<?= strtolower($rating) ?>" data-rating="<?= $rating ?>"><?= $rating ?></button><?php endforeach ?></div></div><?php endforeach ?></div><div class="notes"><label>Descuentos<input id="deductions" type="number" min="0" step="0.25" value="0"></label><label>Observaciones y evidencia<textarea id="observations" placeholder="Registre desviaciones, evidencia o revisión de seguridad."></textarea></label></div><div class="actions"><span class="save-status" id="save-status">Pauta sin guardar</span><button class="button primary" id="complete">Cerrar evaluación</button></div></section></main>
<?php else: ?>
<main><div class="title-row"><div class="title-copy"><h1>1er Torneo<br>Infraestructura<br>Aérea <span class="mt-red">MT</span></h1><span class="safety-badge">Seguridad y disciplina Operativa</span></div><div class="live"><i></i>EN VIVO · <span id="last-update">--:--</span></div></div><div class="score-grid" id="board"><p>Cargando tablero...</p></div><div class="legend"><span><i style="background:#a0bd00"></i>Evaluación cerrada</span><span><i style="background:#ff6b35"></i>En progreso</span><span><i style="background:#f34b55"></i>Detenida por seguridad</span><span>Resultados: promedio de cuatro jueces por prueba</span></div><div class="sponsors"><img src="<?= htmlspecialchars($sponsorsUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Auspiciadores"></div></main>
<?php endif ?>
  <?php if ($boardMode): ?>
  <script>
    (function () {
      var apiUrl = <?= json_encode($apiUrl) ?>;
      var boardNode = document.getElementById('board');
      var updateNode = document.getElementById('last-update');

      function escapeHtml(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
      }

      function format(value) {
        return value === null ? '\u2014' : Number(value).toFixed(1);
      }

      function boardStatus(item) {
        if (item.status === 'suspended') return 'DETENIDA';
        if (item.completed === 4) return 'CERRADA';
        if (item.count) return 'EN CURSO';
        return 'PENDIENTE';
      }

      function renderBoard(board) {
        var html = '';
        var index;
        var item;
        var competitions;
        var id;
        var competition;

        if (Object.prototype.toString.call(board) !== '[object Array]' || !board.length) {
          boardNode.innerHTML = '<p>Aún no hay evaluaciones registradas.</p>';
          return;
        }

        for (index = 0; index < board.length; index++) {
          item = board[index];
          competitions = '';
          for (id in item.competitions) {
            if (Object.prototype.hasOwnProperty.call(item.competitions, id)) {
              competition = item.competitions[id];
              competitions += '<div class="split">0' + escapeHtml(id) + ' · ' + (id === '1' ? 'CRUCETA' : 'POSTE + PPF') + '<b>' + format(competition.score) + '</b><span class="status ' + (competition.status === 'suspended' ? 'suspended' : '') + '">' + boardStatus(competition) + ' · ' + escapeHtml(competition.completed) + '/4</span></div>';
            }
          }
          html += '<article class="team-card" data-tone="' + escapeHtml(item.team.tone) + '"><span class="rank">' + (index + 1 < 10 ? '0' : '') + (index + 1) + ' / 04</span><span class="mark">' + escapeHtml(item.team.mark) + '</span><div class="company">' + escapeHtml(item.team.company) + '</div><div class="team-name">' + escapeHtml(item.team.name) + '</div><div class="total"><span>PUNTAJE ACUMULADO / 200</span><b>' + format(item.total) + '</b><div class="status ' + (item.suspended ? 'suspended' : '') + '">' + (item.suspended ? 'REVISIÓN DE SEGURIDAD' : 'ACTUALIZACIÓN EN VIVO') + '</div><div class="splits">' + competitions + '</div></div></article>';
        }
        boardNode.innerHTML = html;
      }

      function showError(message) {
        boardNode.innerHTML = '<div class="error">' + escapeHtml(message) + '</div>';
      }

      function loadBoard() {
        var request = new XMLHttpRequest();
        request.open('GET', apiUrl + '?action=board', true);
        request.timeout = 10000;
        request.onreadystatechange = function () {
          var data;
          if (request.readyState !== 4) return;
          if (request.status < 200 || request.status >= 300) {
            showError('No fue posible actualizar el tablero.');
            return;
          }
          try {
            data = JSON.parse(request.responseText);
            updateNode.innerHTML = escapeHtml(data.updatedAt || '--:--');
            renderBoard(data.board);
          } catch (error) {
            showError('La respuesta del tablero no es válida.');
          }
        };
        request.onerror = function () { showError('No fue posible conectar con el tablero.'); };
        request.ontimeout = function () { showError('El tablero tardó demasiado en responder.'); };
        request.send(null);
      }

      loadBoard();
      window.setInterval(loadBoard, 5000);
    }());
  </script>
  <?php else: ?>
  <script>
const judge = <?= json_encode($judgeId) ?>;
const competition = <?= $judgeMode ? json_encode($competitionId) : 'null' ?>;
const criteria = <?= $judgeMode ? json_encode($competition['criteria']) : '[]' ?>;
const apiUrl = <?= json_encode($apiUrl) ?>;
const state = {team: 0, ratings: {}, deductions: 0, observations: ''};
const format = value => value === null ? '—' : Number(value).toFixed(1);
async function request(action, options = {}, parameters = {}) { const url = new URL(apiUrl, window.location.origin); url.searchParams.set('action', action); Object.entries(parameters).forEach(([key, value]) => url.searchParams.set(key, value)); const res = await fetch(url, options); const data = await res.json(); if (!res.ok) throw new Error(data.error || 'Error de conexión'); return data; }
function boardStatus(item){if(item.status === 'suspended') return 'DETENIDA'; if(item.completed === 4) return 'CERRADA'; if(item.count) return 'EN CURSO'; return 'PENDIENTE'}
async function loadBoard(){try{const data=await request('board'); const board=Array.isArray(data.board)?data.board:[]; document.querySelector('#last-update').textContent=data.updatedAt||'--:--'; document.querySelector('#board').innerHTML=board.map((item,index)=>`<article class="team-card" data-tone="${item.team.tone}"><span class="rank">${String(index+1).padStart(2,'0')} / 04</span><span class="mark">${item.team.mark}</span><div class="company">${item.team.company}</div><div class="team-name">${item.team.name}</div><div class="total"><span>PUNTAJE ACUMULADO / 200</span><b>${format(item.total)}</b><div class="status ${item.suspended?'suspended':''}">${item.suspended?'REVISIÓN DE SEGURIDAD':'ACTUALIZACIÓN EN VIVO'}</div><div class="splits">${Object.entries(item.competitions).map(([id,c])=>`<div class="split">0${id} · ${id==1?'CRUCETA':'POSTE + PPF'}<b>${format(c.score)}</b><span class="status ${c.status==='suspended'?'suspended':''}">${boardStatus(c)} · ${c.completed}/4</span></div>`).join('')}</div></div></article>`).join('')||'<p>Aún no hay evaluaciones registradas.</p>';}catch(error){document.querySelector('#board').innerHTML=`<div class="error">${error.message}</div>`}}
if(!judge && document.querySelector('#board')) { loadBoard(); setInterval(loadBoard, 5000); }
if(judge && !competition){
  request('progress', {}, {judge}).then(data=>{Object.entries(data.summary).forEach(([id,item])=>{const card=document.querySelector(`[data-competition="${id}"]`), progress=card.querySelector('.evaluation-progress');progress.textContent=item.finished?'Actividad finalizada':`${item.terminal}/4 empresas finalizadas`;if(item.finished)card.classList.add('is-finished')})}).catch(()=>document.querySelectorAll('.evaluation-progress').forEach(item=>item.textContent='No fue posible cargar el estado.'));
}
if(judge && competition){
  const tabs=document.querySelector('#team-tabs'), warning=document.querySelector('#critical-warning'), scoreNode=document.querySelector('#score'), status=document.querySelector('#save-status'), deductions=document.querySelector('#deductions'), observations=document.querySelector('#observations'), complete=document.querySelector('#complete');
  let locked=false;
  function setLocked(evaluationStatus){locked=['completed','suspended','loading'].includes(evaluationStatus);document.querySelectorAll('#criteria button').forEach(button=>button.disabled=locked);deductions.disabled=locked;observations.disabled=locked;complete.disabled=locked;if(['completed','suspended'].includes(evaluationStatus))status.textContent=evaluationStatus==='suspended'?'Prueba detenida y bloqueada para revisión de seguridad.':'Evaluación cerrada y bloqueada.'}
  request('progress', {}, {judge}).then(data=>{const labels={pending:'PENDIENTE',draft:'EN PROGRESO',completed:'CERRADA',suspended:'DETENIDA'};Object.entries(data.progress[competition]||{}).forEach(([teamId,evaluationStatus])=>{const tab=tabs.querySelector(`[data-team="${teamId}"]`);tab.textContent+=` · ${labels[evaluationStatus]}`;tab.title=labels[evaluationStatus]});if(data.summary[competition].finished){status.textContent='Esta actividad ya está finalizada para este juez.';complete.disabled=true;const firstTeam=tabs.querySelector('button');if(firstTeam)selectTeam(Number(firstTeam.dataset.team))}}).catch(()=>status.textContent='No fue posible cargar el estado de las empresas.');
  function isCriticalNC(){return criteria.some((c,i)=>c[1] && state.ratings[i]==='NC')}
  function calculate(){const factors={S:1,C:.75,ED:.5,NC:0};let total=criteria.reduce((sum,c,i)=>sum+c[0]*(factors[state.ratings[i]]??0),0)-Number(state.deductions||0);scoreNode.textContent=Math.max(0,total).toFixed(1);warning.style.display=isCriticalNC()?'block':'none'}
  function paint(){document.querySelectorAll('.criterion').forEach(row=>{let i=row.dataset.index;row.querySelectorAll('button').forEach(b=>b.classList.toggle('active',state.ratings[i]===b.dataset.rating))});calculate()}
    let saveQueue=Promise.resolve(), saveVersion=0, selectionVersion=0;
    async function selectTeam(id){const selection=++selectionVersion;state.team=id;state.ratings={};state.deductions=0;state.observations='';setLocked('loading');tabs.querySelectorAll('button').forEach(b=>b.classList.toggle('active',Number(b.dataset.team)===id));document.querySelector('#selected-team').textContent=tabs.querySelector(`[data-team="${id}"]`).textContent;deductions.value=0;observations.value='';status.textContent='Cargando pauta...';paint();await saveQueue;if(selection!==selectionVersion)return;try{const data=await request('evaluation', {}, {judge, team:id, competition});if(selection!==selectionVersion)return;if(data.evaluation){state.ratings=data.evaluation.ratings||{};state.deductions=data.evaluation.deductions||0;state.observations=data.evaluation.observations||'';deductions.value=state.deductions;observations.value=state.observations;status.textContent=data.evaluation.status==='completed'?'Evaluación cerrada':'Borrador cargado';paint();setLocked(data.evaluation.status)}else{setLocked(null);status.textContent='Pauta nueva';paint()}}catch(error){if(selection===selectionVersion)status.textContent=error.message}}
   tabs.addEventListener('click',e=>{if(e.target.matches('button')) selectTeam(Number(e.target.dataset.team))});
    function save(complete=false){if(locked)return Promise.resolve();if(!state.team){status.textContent='Seleccione una empresa.';return Promise.resolve()}const version=++saveVersion;const payload={judge,competition,team:state.team,ratings:{...state.ratings},deductions:state.deductions,observations:state.observations,complete};status.textContent='Guardando...';saveQueue=saveQueue.catch(()=>{}).then(async()=>{try{const data=await request('save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});if(version===saveVersion&&state.team===payload.team){status.textContent=data.status==='suspended'?'Prueba detenida y registrada para revisión.':complete?'Evaluación cerrada · Puntaje '+Number(data.score).toFixed(1):'Pauta guardada · Puntaje '+Number(data.score).toFixed(1);setLocked(data.status)}}catch(error){if(version===saveVersion&&state.team===payload.team)status.textContent=error.message}});return saveQueue}
  document.querySelector('#criteria').addEventListener('click',e=>{if(!e.target.matches('button'))return;state.ratings[e.target.closest('.criterion').dataset.index]=e.target.dataset.rating;paint();save()});
  document.querySelector('#deductions').addEventListener('input',e=>{state.deductions=e.target.value;calculate()});document.querySelector('#deductions').addEventListener('change',()=>save());document.querySelector('#observations').addEventListener('input',e=>state.observations=e.target.value);document.querySelector('#observations').addEventListener('change',()=>save());
  document.querySelector('#complete').addEventListener('click',()=>{if(!state.team){status.textContent='Seleccione una empresa.';return}if(Object.keys(state.ratings).length!==criteria.length){status.textContent='Califique todos los indicadores antes de cerrar.';return}save(!isCriticalNC())});
}
  </script>
  <?php endif ?>
</body></html>
