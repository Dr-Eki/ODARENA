<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Round;

class ChroniclesHelper
{
    public function getMaxRoundNumber(): int
    {
        return Round::max('number');
    }

    public function getDefaultRoundsAgo(): int
    {
        return 20;
    }

    public function getAlignmentBoxClass(string $alignment): string
    {
        switch($alignment)
        {
            case 'evil':
                return 'danger';
            case 'good':
                return 'info';
            case 'independent':
                return 'warning';
            default:
                return 'primary';
        }
    }
}
