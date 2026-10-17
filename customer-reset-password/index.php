<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = '../';
$assetBase = '../';
$customerLoginPath = '../customer-login/';
$customerForgotPasswordPath = '../customer-forgot-password/';
require dirname(__DIR__) . '/reset_password_page_customer.php';