<?php
return [
    // 'gmail' = real emails through Gmail, 'mailpit' = local testing inbox
    'driver' => 'gmail',

    'gmail' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,        // if blocked, use 465 and set encryption to 'ssl'
        'encryption' => 'tls',      // 'tls' for port 587, 'ssl' for port 465
        'username'   => 'heisenberg3556walter@gmail.com',
        'password'   => 'pugxjymmynjhpubh',   // App Password, no spaces
    ],

    'mailpit' => [
        'host' => '127.0.0.1',
        'port' => 1025,
    ],

    // 'from_email' must be the same address as the Gmail username above
    'from_email' => 'tanvinceangelo1030@gmail.com',
    'from_name'  => 'The Nifty Fifty',

    // set to true only while troubleshooting (prints SMTP details on the page)
    'debug' => false,
];
