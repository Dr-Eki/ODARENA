<?php

# app/config/phasing.php

return [

    'aurei' =>
        [
            'halcyon' => [
                    'chryson' => [
                        'target_unit_key' => 'chryson',
                        'resource' => 'mana',
                        'resource_amount' => 222
                    ]
                ],

            'chryson' => [
                    'halcyon' => [
                        'target_unit_key' => 'halcyon',
                        'resource' => 'mana',
                        'resource_amount' => 111
                    ]
                ],
        ]

];