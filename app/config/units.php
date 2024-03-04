<?php

# app/config/units.php

$states = [
    1 => 'home',
    2 => 'training',
    3 => 'invasion',
    4 => 'artefactattack',
    5 => 'summoning',
    6 => 'evolution',
    7 => 'stun',
    8 => 'desecration',
    9 => 'expedition',
    10 => 'sabotage',
    11 => 'recovery'
];

return [

    'states' => $states,

    'states_types' => [

        'home' => [
            'home',
            'stun',
            'recovery'
        ],

        'available' => [
            'home',
        ],

        'returning' => [
            'invasion',
            'artefactattack',
            'desecration',
            'expedition',
            'sabotage'
        ],

        'training' => [
            'training',
            'summoning',
            'evolution'
        ],

        'paid' => array_values($states)

    ]
];