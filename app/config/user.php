<?php

# app/config/user.php

return [
    'avatar' => [

        'generator' => 'stability',

        'generate_x' => 512,
        'generate_y' => 512,

        'fit_x' => 512,
        'fit_y' => 512,

        'display_x' => 128,
        'display_y' => 128,
    ],
];