<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$router = require base_path('routes/web.php');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);