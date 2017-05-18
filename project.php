<?php

session_cache_limiter(false);
session_start();

require_once 'vendor/autoload.php';

DB::$host = '127.0.0.1';
DB::$user = 'phpwebforum';
DB::$password = '5zAijLF4Ooaojs6O';
DB::$dbName = 'phpwebforum';
DB::$port = '8008';
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

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('user', $_SESSION['user']);

$app->get('/', function() use ($app) {
    if(!$_SESSION['user']) {
       $app->render('index_please_login.html.twig');
        return; 
    }
    // FIXME: incorrect table title and cell names
    $userId = $_SESSION['user']['id'];
    $userList = DB::query("SELECT * FROM users WHERE ownerId=%i", $userId);
    print_r($userList);
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
        $_SESSION['user'] = $user;
        $app->render('login_success.html.twig');
    }
});


$app->get('/logout', function() use ($app) {
    unset($_SESSION['user']);
    $app->render('logout.html.twig');
});

// end login block

// PASSWORD RESET

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$app->map('/passreset', function () use ($app, $log) {
    // Alternative to cron-scheduled cleanup
    if (rand(1,1000) == 111) {
        // TODO: do the cleanup 1 in 1000 accessed to /passreset URL
    }
    if ($app->request()->isGet()) {
        $app->render('passreset.html.twig');
    } else {
        $email = $app->request()->post('email');
        $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
        if ($user) {
            $app->render('passreset_success.html.twig'); // ADD THIS TO TEMP
            $secretToken = generateRandomString(50);
            // VERSION 1: delete and insert
            /*
              DB::delete('passresets', 'userID=%d', $user['ID']);
              DB::insert('passresets', array(
              'userID' => $user['ID'],
              'secretToken' => $secretToken,
              'expiryDateTime' => date("Y-m-d H:i:s", strtotime("+5 hours"))
              )); */
            // VERSION 2: insert-update TODO
            DB::insertUpdate('passresets', array(
                'userID' => $user['ID'],
                'secretToken' => $secretToken,
                'expiryDateTime' => date("Y-m-d H:i:s", strtotime("+5 minutes"))
            ));
            // email user
            $url = 'http://' . $_SERVER['SERVER_NAME'] . '/passreset/' . $secretToken;
            $html = $app->view()->render('email_passreset.html.twig', array(
                'name' => $user['name'],
                'url' => $url
            ));
            $headers = "MIME-Version: 1.0\r\n";
            $headers.= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers.= "From: Noreply <noreply@ipd8.info>\r\n";
            $headers.= "To: " . htmlentities($user['name']) . " <" . $email . ">\r\n";

            mail($email, "Password reset from SlimShop", $html, $headers);
        } else {
            $app->render('passreset.html.twig', array('error' => TRUE));
        }
    }
})->via('GET', 'POST');

$app->get('/scheduled/daily', function() use ($app, $log) {
    DB::$error_handler = FALSE;
    DB::$throw_exception_on_error = TRUE;
            // PLACE THE ORDER
    $log->debug("Daily scheduler run started");
    // 1. clean up old password reset requests
    try {
        DB::delete('passresets', "expiryDateTime < NOW()");    
        $log->debug("Password resets clean up, removed " . DB::affectedRows());
    } catch (MeekroDBException $e) {
        sql_error_handler(array(
                    'error' => $e->getMessage(),
                    'query' => $e->getQuery()
                ));
    }
   
});

$app->map('/passreset/:secretToken', function($secretToken) use ($app) {
    $row = DB::queryFirstRow("SELECT * FROM passresets WHERE secretToken=%s", $secretToken);
    if (!$row) {
        $app->render('passreset_notfound_expired.html.twig');
        return;
    }
    if (strtotime($row['expiryDateTime']) < time()) {
        $app->render('passreset_notfound_expired.html.twig');
        return;
    }
    //
    if ($app->request()->isGet()) {
        $app->render('passreset_form.html.twig');
    } else {
        $pass1 = $app->request()->post('pass1');
        $pass2 = $app->request()->post('pass2');
        // TODO: verify password quality and that pass1 matches pass2
        $errorList = array();
        $msg = verifyPassword($pass1);
        if ($msg !== TRUE) {
            array_push($errorList, $msg);
        } else if ($pass1 != $pass2) {
            array_push($errorList, "Passwords don't match");
        }
        //
        if ($errorList) {
            $app->render('passreset_form.html.twig', array(
                'errorList' => $errorList
            ));
        } else {
            // success - reset the password
            DB::update('users', array(
                'password' => password_hash($pass1, CRYPT_BLOWFISH)
                    ), "ID=%d", $row['userID']);
            DB::delete('passresets','secretToken=%s', $secretToken);
            $app->render('passreset_form_success.html.twig');
        }
    }
})->via('GET', 'POST');

// END PASSWORD RESET


// board code

$app->run();
