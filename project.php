<?php

date_default_timezone_set('EST');

session_cache_limiter(false);
session_start();

require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

DB::$host = '127.0.0.1';
DB::$user = 'phpwebforum';
DB::$password = '5zAijLF4Ooaojs6O';
DB::$dbName = 'phpwebforum';
DB::$port = 3333;
DB::$encoding = 'utf8';

DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    $app->render('error_internal.html.twig');
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    $app->render('error_internal.html.twig');
    die; // don't want to keep going if a query broke
}

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

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('user', $_SESSION['user']);

//$app->get('/', function() use ($app) {
//    $app->render('index_please_login.html.twig');
//});
// REGISTER FORM
$app->get('/register', function() use ($app) {
    $app->render('register.html.twig');
});

// Receiving a submission
$app->post('/register', function() use ($app) {
    // extract variables
    $username = $app->request()->post('username');
    $pass1 = $app->request()->post('pass1');
    $pass2 = $app->request()->post('pass2');
    // list of values to retain after a failed submission
    $valueList = array('username' => $username);
    // check for errors and collect error messages
    $errorList = array();

    if ($username) {
        $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
    } else {
        array_push($errorList, "Username already in use");
    }
    if ($pass1 != $pass2) {
        array_push($errorList, "Passwors do not match");
    } else {
        if (strlen($pass1) < 6) {
            array_push($errorList, "Password too short, must be 6 characters or longer");
        }
        if (preg_match('/[A-Z]/', $pass1) != 1 || preg_match('/[a-z]/', $pass1) != 1 || preg_match('/[0-9]/', $pass1) != 1) {
            array_push($errorList, "Password must contain at least one lowercase, "
                    . "one uppercase letter, and a digit");
        }
    }
    //
    if ($errorList) {
        $app->render('register.html.twig', array(
            'errorList' => $errorList,
            'v' => $valueList
        ));
    } else {
        DB::insert('users', array(
            'username' => $username,
            'password' => $pass1
        ));
        $app->render('register_success.html.twig');
    }
});

// END REGISTER
// LOGIN FORM
$app->get('/login', function() use ($app) {
    $app->render('login.html.twig');
});

$app->post('/login', function() use ($app) {
    $username = $app->request()->post('username');
    $pass = $app->request()->post('pass');
    // verification    
    $error = false;
    $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
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
        $_SESSION['user'] = $user;
        $app->render('login_success.html.twig');
    }
});

$app->get('/logout', function() use ($app) {
    unset($_SESSION['user']);
    $app->render('logout.html.twig');
});

// END LOGIN
// BOARD CODE

$app->get('/', function() use ($app) {
    $boardList = DB::query("SELECT * FROM boards");
    $app->render('board_list.html.twig', array("boardList" => $boardList));
});

$app->get('/search', function() use ($app) {
    $keywords = $app->request()->get('keywords');
    // fixmeeee
});

//// list of threads in a board
$app->get('/board/:id', function($id) use ($app) {
    $board = DB::queryFirstRow('SELECT * FROM boards WHERE boardId=%i', $id);
    $threadList = DB::query('SELECT * FROM threads WHERE boardId=%i', $id);
    $app->render('board_view.html.twig', array(
        'board' => $board, 'threadList' => $threadList
    ));
});

// VIEW/CREATE THREAD
// view of one thread on a board
$app->get('/thread/:threadId', function($threadId) use ($app) {
    $thread = DB::queryFirstRow("SELECT * FROM threads WHERE threadId=%i", $threadId);
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId", $threadId);
    $app->render('thread_view.html.twig', array('thread' => $thread, 'postList' => $postList));
});

// 3-state form to create new thread in a board
$app->get('/board/:boardId/newthread', function($boardId) use ($app) {
    // FIXME: only logged in users can access
    $board = DB::queryFirstRow("SELECT * FROM boards WHERE boardId=%i", $boardId);
    $app->render('thread_new.html.twig', array('board' => $board));
    // print_r($boardId);
});

$app->post('/board/:boardId/newthread', function($boardId) use ($app) {
    // FIXME: only logged in users can access
    if (!$_SESSION['user']) {
        $app->render('forbidden.html.twig');
        return;
    }
    $userId = $_SESSION['user']['userId'];
    $title = $app->request()->post('title');
    $body = $app->request()->post('body');
    $postList = array('title' => $title, 'body' => $body);
    // verify inputs
    $errorList = array();
    if (strlen($title) < 2 || strlen($title) > 100) {
        array_push($errorList, "Title must be between 2 and 100 characters");
    }
    if (strlen($body) < 1 || strpos($body, 'Enter body here...') !== false) {
        array_push($errorList, "Body must not be empty");
    }
    // TODO: generate date according to date of thread creation    
    $date = date('m/d/Y h:i:s a', time());
    // receive data and insert
    if (!$errorList) {
        DB::insert('threads', array(
            'boardId' => $boardId,
            'title' => $title,
            'date' => $date
        ));
        $threadId = DB::insertId();
        DB::insert('posts', array(
            'threadId' => $threadId,
            'userId' => $userId,
            'title' => $title,
            'body' => $body,
            'date' => $date
        ));
        $app->render('thread_new_success.html.twig', array('threadId' => $threadId));
    } else {
        $app->render('thread_new.html.twig', array(
            'p' => $postList
        ));
    }
});


// REPLY TO THREAD

$app->get('/thread/:threadId', function($threadId) use ($app) {
    // only allow submission if user is logged in 
    if (!$_SESSION['user']) {
        $app->render('forbidden.html.twig');
        return;
    }
    $thread = DB::queryFirstRow("SELECT * FROM threads WHERE threadId=%i", $threadId);
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId", $threadId);
    $app->render('thread_view.html.twig',  array('thread' => $thread, 'postList' => $postList));
});

$app->post('/thread/:threadId', function($threadId) use ($app) {
    // handle the submission
    $body = $app->request()->post('body');
    // verify body not too long / short
    $errorList = array();
    if (strlen($body) < 1) {
        array_push($errorList, "Body cannot be empty when replying to a thread");
    }
    // insert into posts
    if (!$errorList) {
       // $threadId = $_SESSION['user']['threadId'];
        DB::insert('posts', array(
            'userId' => $userId, // FIXME
            'threadId' => $threadId,
            'body' => $body
        ));
        $app->render('thread_view.html.twig');
    }
    $thread = DB::queryFirstRow("SELECT * FROM threads WHERE threadId=%i", $threadId);
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId", $threadId);
    $app->render('thread_view.html.twig', array('thread' => $thread, 'postList' => $postList));
});

//// 3-state form to reply to a thread
//$app->get('/thread/:threadId/reply', function() use ($app) {
//    $app->render('thread_view.html.twig');
//});
//$app->post('/thread/:threadId/reply', function() use ($app) {
//    $title = $app->request()->post('title');
//    $body = $app->request()->post('body');
//    $postList = array('title' => $title, 'body' => $body);
//    // verify inputs
//    $errorList = array();
//    if (strlen($title) < 2 || strlen($title) > 100) {
//        array_push($errorList, "Title must be between 2 and 100 characters");
//    }
//    if (strlen($body) < 1) {
//        array_push($errorList, "Body cannot be empty when replying to a thread");
//    }
//    // TODO: generate date according to date of reply/post
//    $date = date('m/d/Y h:i:s a', time());
//    // receive data and insert
//    if (!$errorList) {
//        $threadId = $_SESSION['user']['id'];
//        DB::insert('thread', array(
//            'threadId' => $threadId,
//            'title' => $title,
//            'body' => $body
//        ));
//        $app->render('reply_new_success.html.twig');
//    } else {
//        $app->render('reply_new.html.twig', array(
//            'p' => $postList
//        ));
//    }
//});


$app->run();
