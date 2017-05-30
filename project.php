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

//DB::$host = '127.0.0.1';
DB::$user = 'cp4776_webforum';
DB::$password = '5zAijLF4Ooaojs6O';
DB::$dbName = 'cp4776_webforum';
//DB::$port = 3333;
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
        $app->redirect('/');
    }
});

$app->get('/logout', function() use ($app) {
    unset($_SESSION['user']);
    $app->redirect('/');
});

// END LOGIN
// BOARD CODE
$app->get('/', function() use ($app) {
    $boardList = DB::query("SELECT * FROM boards");
    $app->render('board_list.html.twig', array("boardList" => $boardList));
});

// 3-state form to create new board
$app->get('/board/newboard', function() use ($app) {
    if (!$_SESSION['user']) {
        $app->render('login.html.twig');
        return;
    }
    $app->render('board_new.html.twig');
});

$app->post('/board/newboard', function() use ($app) {
    if (!$_SESSION['user']) {
        $app->render('login.html.twig');
        return;
    }
    $title = $app->request()->post('title');
    // verify inputs
    $errorList = array();
    if (strlen($title) < 2 || strlen($title) > 100) {
        array_push($errorList, "Title must be between 2 and 100 characters");
    }   
    if ($errorList) {
        $app->render('board_list.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        DB::insert('boards', array(
            'title' => $title
        ));
        $app->redirect('/');
    }
});

// END BOARD CODE

// SEARCH CODE
$app->get('/search', function() use ($app) {
    $keywords = $app->request()->get('keywords');
    $searchResults = DB::query("SELECT * FROM posts WHERE body LIKE %ss0", $keywords);
    $app->render('search_results.html.twig', array("searchResults" => $searchResults));
});

// END SEARCH

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
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId ORDER BY date ASC", $threadId);
    $app->render('thread_view.html.twig', array('thread' => $thread, 'postList' => $postList));
});

// 3-state form to create new thread in a board
$app->get('/board/:boardId/newthread', function($boardId) use ($app) {
    if (!$_SESSION['user']) {
        $app->render('login.html.twig');
        return;
    }
    $board = DB::queryFirstRow("SELECT * FROM boards WHERE boardId=%i", $boardId);
    $app->render('thread_new.html.twig', array('board' => $board));
    // print_r($boardId);
});

$app->post('/board/:boardId/newthread', function($boardId) use ($app) {
    if (!$_SESSION['user']) {
        $app->render('login.html.twig');
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
    if (strlen($body) == 0) {
        array_push($errorList, "Body must not be empty");
    } 
    $date = date('Y-m-d H:i:s');
    // receive data and insert
    
    if ($errorList) {
        $app->render('thread_new.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        DB::insert('threads', array(
            'boardId' => $boardId,
            'title' => $title,
            'date' => $date
        ));
        $threadId = DB::insertId();
        DB::insert('posts', array(
            'threadId' => $threadId,
            'userId' => $userId,
            'body' => $body,
            'date' => $date
        ));
        $app->render('thread_new_success.html.twig', array('threadId' => $threadId, 'p' => $postList));
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
    $boardId = $thread['boardId'];
    $board = DB::query("SELECT * FROM boards WHERE boardId=%i", $boardId);
    $boardName = $board['title'];
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId", $threadId);
    $app->render('thread_view.html.twig',  array('boardName' => $boardName, 'thread' => $thread, 'postList' => $postList));
});

$app->post('/thread/:threadId', function($threadId) use ($app) {
    // handle the submission
    $userId = $_SESSION['user']['userId'];
    $body = $app->request()->post('body');
    $date = date('Y-m-d H:i:s');
        // verify body not too long / short
    $errorList = array();
    if (strlen($body) < 1) {
        array_push($errorList, "Body cannot be empty when replying to a thread");
    }
    // insert into posts
    if (!$errorList) {
       // $threadId = $_SESSION['user']['threadId'];
        DB::insert('posts', array(
            'threadId' => $threadId,
            'userId' => $userId,
            'body' => $body,
            'date' => $date
        ));
    }
    $thread = DB::queryFirstRow("SELECT * FROM threads WHERE threadId=%i", $threadId);
    $postList = DB::query("SELECT p.postId, p.body, p.date, u.username FROM posts p, users u WHERE p.threadId=%i AND u.userId=p.userId", $threadId);
    $app->render('thread_view.html.twig', array('thread' => $thread, 'postList' => $postList));
});


$app->run();
