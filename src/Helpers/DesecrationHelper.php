<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\GameEvent;

class DesecrationHelper
{
    public function __construct()
    {
    }

    public function getDesecrationTargetTypeString(GameEvent $gameEvent): string
    {
        switch ($gameEvent->type)
        {
            case 'invasion':
                return 'battlefield';
            
            case 'barbarian_invasion':
                return 'battlefield';

            default:
                return $gameEvent->type;
        }
    }


}
