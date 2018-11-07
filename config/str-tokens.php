<?php

return [

    /* -----------------------------------------------------------------
     |  Date formats in tokens
     | -----------------------------------------------------------------
     */
    'date' => [
        'formats' => [
            'short' => 'm/d/Y - H:i',
            'medium' => 'D, m/d/Y - H:i',
            'long' => 'l, F j, Y - H:i',
            'time' => 'H:i:s',
            'date' => 'd.m.Y',
            'my' => 'm/y', // You can make own date format name: [date:my]
        ],
    ],


    /* -----------------------------------------------------------------
     |  Set pattern for safe app configs
     | -----------------------------------------------------------------
     */
    'disable_configs' => [
        'app.key',
        'auth.*',
        'mail.*',
        'services.*',
        'password',
        '*token*',
    ],
];
