<?php

# app/config/factions.php

/*

    race->key => [
        'starting_units' => [
            'unit_key' => amount,
        ],
        'starting_buildings' => [
            'building_key' => amount,
        ],
        'starting_resources' => [
            'resource_key' => amount,
        ],
    ]

*/

return [

    'demon' => [
        'starting_units' => [
            'archdemon' => 1,
        ],
    ],

    'growth' => [
        'starting_draft_rate' => 100,
    ],

    'kerranad' => [
        'starting_buildings' => [
            'aqueduct' => 25,
            'constabulary' => 25,
            'farm' => 50,
            'gold_mine' => 100,
            'harbour' => 50,
            'infirmary' => 50,
            'ore_mine' => 100,
            'residence' => 50,
            'saw_mill' => 50,
            'tavern' => 50,
            'tower' => 50,
            'wizard_guild' => 50,
            'syndicate_quarters' => 50,
            'gem_mine' => 300,
        ],

        'starting_resources' => [
            'gems' => 500000,
        ],
    ],

    'undead' => [
        'starting_resources' => [
            'body' => 4000,
        ],
    ]


];