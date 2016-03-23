<?php

require_once __DIR__ . '/../app/bootstrap.php';

$app = new \Silex\Application();
$app['debug'] = false;

require APP_PATH . '/config/config-dev.php';

require APP_PATH . '/app.php';

$app->run();