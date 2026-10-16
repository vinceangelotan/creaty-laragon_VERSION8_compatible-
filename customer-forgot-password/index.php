<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = '../';
$assetBase = '../';
$customerLoginPath = '../customer-login/';
$customerResetPasswordPath = '../customer-reset-password/';
require dirname(__DIR__) . '/forgot_password_page_customer.php';