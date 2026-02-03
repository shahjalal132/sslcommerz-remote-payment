<?php

if (!defined('PROJECT_PATH')) {
    define('PROJECT_PATH', 'http://localhost:8080'); // replace this value with your project path
}

if (!defined('IS_SANDBOX')) {
    define('IS_SANDBOX', true); // 'true' for sandbox, 'false' for live
}

if (!defined('STORE_ID')) {
    define('STORE_ID', 'nixso6952034d24c43'); // your store id. For sandbox, register at https://developer.sslcommerz.com/registration/
}

if (!defined('STORE_PASSWORD')) {
    define('STORE_PASSWORD', 'nixso6952034d24c43@ssl'); // your store password.
}

return [
    'success_url' => 'wp-json/api/v1/payment/success', // your success url
    'failed_url' => 'wp-json/api/v1/payment/fail', // your fail url
    'cancel_url' => 'wp-json/api/v1/payment/cancel', //your cancel url
    'ipn_url' => 'wp-json/api/v1/payment/ipn', // your ipn url


    'projectPath' => PROJECT_PATH,
    'apiDomain' => IS_SANDBOX ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com',
    'apiCredentials' => [
        'store_id' => STORE_ID,
        'store_password' => STORE_PASSWORD,
    ],
    'apiUrl' => [
        'make_payment' => "/gwprocess/v4/api.php",
        'order_validate' => "/validator/api/validationserverAPI.php",
    ],
    'connect_from_localhost' => false,
    'verify_hash' => true,
];
