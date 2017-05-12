<?php

session_cache_limiter(false);
session_start();

require_once 'vendor/autoload.php';

DB::$host = '127.0.0.1';
DB::$user = 'slimtodo';
DB::$password = 'd2f1rzcbTRKx3sqp';
DB::$dbName = 'slimtodo';
DB::$port = '8005';
DB::$encoding = 'utf8';

// Slim creation and setup
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');

if (!isset($_SESSION['todouser'])) {
    $_SESSION['todouser'] = array();
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('todouser', $_SESSION['todouser']);



$app->run();
