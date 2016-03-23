<?php

if (!file_exists($loader = __DIR__ . '/../vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run this script.');
}

return require_once $loader;
