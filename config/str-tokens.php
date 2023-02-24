<?php

return [

    /* -----------------------------------------------------------------
     |  Customize how your tokens are parsed
     | -----------------------------------------------------------------
     */
    // 'can_traverse_relations' => false,
    // 'token_split_character' => '.',
    // 'token_match_pattern' => '/
    // \\{\\{
    // ([^\\s\\{\\{\\}\\}\.]*)
    // \.
    // ([^\\{\\{\\}\\}]*)
    // \\}\\}
    // /x',
    
    
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
     |  Patterns disabled configs use in token
     | -----------------------------------------------------------------
     */
    'disable_configs' => [
        'account.account',
        'app.key',
        'auth.*',
        'mail.*',
        'services.*',
        'password',
        '*token*',
    ],
    /* -----------------------------------------------------------------
     |  Patterns disabled configs use in token
     | -----------------------------------------------------------------
     */
    // 'disable_model_tokens' => [],
    
];
