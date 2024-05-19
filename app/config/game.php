<?php

# app/config/game.php

return [

    'extended_logging' => true,
    'extended_logging_with_dump' => true,

    'starting_land' => 1000,

    'defaults' => [
        'unit_return_ticks' => 12,
        'unit_training_ticks' => 12,
        'construction_ticks' => 12,
        'rezoning_ticks' => 12,
        'queue_ticks' => 12,
        
        'building_housing' => 15,
        'building_jobs' => 20,
    ],
];