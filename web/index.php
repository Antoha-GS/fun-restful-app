<?php

require_once __DIR__ . '/../app/bootstrap.php';

$app = new \Silex\Application();

require APP_PATH . '/config/config-prod.php';

require APP_PATH . '/app.php';

$app->run();