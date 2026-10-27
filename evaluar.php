<?php
declare(strict_types=1);

// This dedicated route has no session timeout; evaluation state is restored from MySQL.
$_GET['vista'] = 'jueces';
require __DIR__ . '/index.php';
