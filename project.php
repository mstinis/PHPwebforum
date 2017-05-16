<?php

session_cache_limiter(false);
session_start();

require_once 'vendor/autoload.php';

DB::$host = '127.0.0.1';
DB::$user = 'phpwebforum';
DB::$password = '5zAijLF4Ooaojs6O';
DB::$dbName = 'phpwebforum';
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

if (!isset($_SESSION['forumuser'])) {
    $_SESSION['forumuser'] = array();
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('todouser', $_SESSION['todouser']);

$app->get('/', function() use ($app) {
    if(!$_SESSION['forumuser']) {
       $app->render('index_please_login.html.twig');
        return; 
    }
    // FIXME: incorrect table title and cell names
    $userId = $_SESSION['forumuser']['id'];
    $todoList = DB::query("SELECT * FROM forum WHERE ownerId=%i", $userId);
    print_r($todoList);
});

// STATE 1: First show
$app->get('/register', function() use ($app) {
    $app->render('register.html.twig');
});

// AJAX: Is user with this username (or email) already registered?
// FIXME: Make link to username, AND email
$app->get('/ajax/emailused/:email', function($email) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    //echo json_encode($user, JSON_PRETTY_PRINT);
    echo json_encode($user != null);    
});

// HOMEWORK 1: implement login form
$app->get('/login', function() use ($app) {
    $app->render('login.html.twig');
});

$app->post('/login', function() use ($app) {
//    print_r($_POST);    
    $email = $app->request()->post('email');
    $pass = $app->request()->post('pass');
    // verification    
    $error = false;
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    if (!$user) {
        $error = true;
    } else {
        if ($user['password'] != $pass) {
            $error = true;
        }
    }
    // decide what to render
    if ($error) {
        $app->render('login.html.twig', array("error" => true));
    } else {
        unset($user['password']);
        $_SESSION['todouser'] = $user;
        $app->render('login_success.html.twig');
    }
});
// end login block


$app->run();
