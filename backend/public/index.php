<?php

/*
 *---------------------------------------------------------------
 * APPLICATION ENTRY POINT
 *---------------------------------------------------------------
 * SmugFlex POS - Enterprise Point of Sale System
 * CodeIgniter 4 REST API Backend
 */

define('WRITEPATH', __DIR__ . '/../writable/');
define('PUBLICPATH', __DIR__);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

$pathsConfig = $_ENV['paths'] ?? Paths::class;
$routesConfig = $_ENV['routes'] ?? Routes::class;

(CodeIgniter\CodeIgniter::initiate(
    new $pathsConfig()
))->run(new $routesConfig());
