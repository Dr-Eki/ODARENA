<?php

# app/config/game.php

return [

    'extended_logging' => true,

    'starting_land' => 1000,

    'defaults' => [
        'unit_return_ticks' => 12,
        'unit_training_ticks' => 12,
        'construction_ticks' => 12,
        'rezoning_ticks' => 12,
        'queue_ticks' => 12,
    ],
];