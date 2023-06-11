<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;


class PackHelper
{
    public function __construct()
    {
    }

    public function getPackStatusString(Pack $pack): string
    {
        switch ($pack->status)
        {
            case 0:
                return 'Private';
            case 1:
                return 'Public';
            case 2:
                return 'Closed';
            default:
                return 'Unknown';
        }
    }

}
