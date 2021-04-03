<?php

namespace OpenDominion\Helpers;


class RealmHelper
{
    public function getAlignmentNoun(string $alignment): string
    {
        if($alignment === 'good')
        {
            return 'Commonwealth';
        }
        elseif($alignment === 'evil')
        {
            return 'Empire';
        }
        elseif($alignment === 'independent')
        {
            return 'Independent';
        }
        elseif($alignment === 'npc')
        {
            return 'Barbarian';
        }
        else
        {
            return $string;
        }
    }

    public function getAlignmentAdjective(string $alignment)
    {
        if($alignment === 'independent')
        {
            return 'Independent';
        }
        else
        {
            return $this->getAlignmentNoun($string);
        }

    }
}
