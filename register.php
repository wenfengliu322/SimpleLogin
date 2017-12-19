<?php
/**
 * Created by PhpStorm.
 * User: Liu
 */

require_once 'class/user.php';
require_once 'config.php';

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$pass = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

if($user->registration($email, $username, $pass)) {
    print 'An activation email has been sent, please confirm your account registration!';
    die;
} else {
    $user->printMsg();
    die;
}