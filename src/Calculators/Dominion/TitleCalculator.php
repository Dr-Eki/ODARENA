<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Helpers\TitleHelper;


class TitleCalculator
{
    protected $titleHelper;

    public function __construct()
    {
        $this->titleHelper = app(TitleHelper::class);
    }

    public function canChangeTitle(Dominion $dominion): bool
    {
        return ($dominion->history()->count() == 0 and $dominion->protection_ticks == 96);
    }

}
