<?php
require 'config.php';

session_start();
if (isset($_SESSION["loggedin"])) {
    $_SESSION = array();

    session_destroy();

    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

header("Location: index.php");
exit();
?>