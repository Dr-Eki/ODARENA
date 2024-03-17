<?php

# app/config/units.php

$states = [
    0 => 'none', # Default state
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

    ],

    'special_plural_units' => [
        'berserk' => 'berserkir',
        'cavalry' => 'cavalry',
        'einherjar' => 'einherjar',
        'envoy of darkness' => 'Envoys of Darkness',
        'fallen' => 'fallen',
        'goat witch' => 'goat witches',
        'hex' => 'hex',
        'huskarl' => 'huskarlar',
        'lich' => 'liches',
        'nix' => 'nix',
        'pax' => 'pax',
        'norn' => 'nornir',
        'shaman' => 'shamans',
        'snow witch' => 'snow witches',
        'valkyrja' => 'valkyrjur',
        'vex' => 'vex',
    ],
];