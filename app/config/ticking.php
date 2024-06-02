<?php

# app/config/ticking.php

return [

    'queue_retry_attempts' => 60, 
    'queue_check_delay' => 500, # milliseconds

    'queue_opening_delay' => 1000000, # microseconds
    'queue_closing_delay' => 1000000, # microseconds

    'deadlock_retry_attempts' => 10,
    'deadlock_retry_delay' => 1000000, # microseconds

];