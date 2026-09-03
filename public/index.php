<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Core\App;

$app = new App(BASE_PATH . '/config/config.php');
$app->loadRoutes();
$app->run();
