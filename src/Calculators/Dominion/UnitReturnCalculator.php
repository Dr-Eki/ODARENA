<?php

namespace OpenDominion\Calculators\Dominion;

use Carbon\Carbon;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Unit;

use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class UnitReturnCalculator
{
    protected $magicCalculator;
    protected $militaryCalculator;
    protected $unitHelper;

    public function __construct()
    {
        $this->magicCalculator = app()->make(MagicCalculator::class);
        $this->militaryCalculator = app()->make(MilitaryCalculator::class);
        $this->unitHelper = app()->make(UnitHelper::class);
    }
 
    public function getUnitReturnTime(Dominion $dominion, Unit $unit, $eventType = 'invasion', array $units = []): int
    {
        return max((int)floor($this->getUnitBaseReturnTime($dominion, $unit, $eventType) * $this->getUnitReturnTimeMultiplier($dominion, $unit, $eventType, $units)), 1);
    }

    public function getUnitBaseReturnTime(Dominion $dominion, Unit $unit, string $eventType = 'invasion'): int
    {
        $ticks = $unit->getPerkValue('return_time') ?? 12;

        $ticks -= $unit->getPerkValue('faster_return');
        $ticks -= $dominion->getSpellPerkValue('faster_return');
        $ticks -= $dominion->getAdvancementPerkValue('faster_return');
        $ticks -= $dominion->realm->getArtefactPerkValue('faster_return');

        $ticks -= $unit->getPerkValue('faster_return_on_' . $eventType);
        $ticks -= $dominion->getSpellPerkValue('faster_return_on_' . $eventType);
        $ticks -= $dominion->getAdvancementPerkValue('faster_return_on_' . $eventType);
        $ticks -= $dominion->realm->getArtefactPerkValue('faster_return_on_' . $eventType);

        $ticks -= floor($dominion->realm->getArtefactPerkValue('faster_return_from_wizard_ratio') * $this->magicCalculator->getWizardRatio($dominion));
        $ticks -= floor($dominion->realm->getArtefactPerkValue('faster_return_from_spy_ratio') * $this->militaryCalculator->getSpyRatio($dominion));

        $ticks -= $this->getFasterReturnFromTerrainPerk($dominion, $unit);
        $ticks -= $this->getFasterReturnFromTimePerk($dominion, $unit);

        $ticks = (int)floor($ticks);
        $ticks = max(1, $ticks);

        return $ticks;

    }

    public function getUnitReturnTimeMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        $multiplier += $dominion->getImprovementPerkMultiplier('faster_return');
        $multiplier += $dominion->getBuildingPerkMultiplier('faster_return');


        return $multiplier;
    }

    public function getFasterReturnFromTerrainPerk(Dominion $dominion, Unit $unit): float
    {
        if($fasterReturnFromTerrainPerk = $unit->getPerkValue('faster_return_from_terrain'))
        {

            $perChunk = $fasterReturnFromTerrainPerk[0];
            $chunkSize = $fasterReturnFromTerrainPerk[1];
            $terrainKey = $fasterReturnFromTerrainPerk[2];
            $maxPerk = $fasterReturnFromTerrainPerk[3];

            $ticksFaster = ($dominion->{'terrain_' . $terrainKey} / $dominion->land) * 100 / $chunkSize * $perChunk;
            $ticksFaster = min($ticksFaster, $maxPerk);
            $ticksFaster = floor($ticksFaster);

           return $ticksFaster;
        }

        return 0;
    }

    public function getFasterReturnFromTimePerk(Dominion $dominion, Unit $unit): float
    {
        if($fasterReturnFromTimePerk = $unit->getPerkValue('faster_return_from_time'))
        {
            $hourFrom = $fasterReturnFromTimePerk[0];
            $hourTo = $fasterReturnFromTimePerk[1];
        
            if (Carbon::now()->hour >= min($hourFrom, $hourTo) && Carbon::now()->hour < max($hourFrom, $hourTo))
            {
                return (int)$fasterReturnFromTimePerk[2];
            }
        }

        return 0;
    }

    public function getSlowestUnitReturnTime(Dominion $dominion, array $units): int
    {
        $returnTimes = [];

        foreach ($units as $unit) {
            $returnTimes[] = $this->getUnitReturnTime($dominion, $unit);
        }

        return max($returnTimes);
    }

    public function getFastestUnitReturnTime(Dominion $dominion, array $units): int
    {
        $returnTimes = [];

        foreach ($units as $unit) {
            $returnTimes[] = $this->getUnitReturnTime($dominion, $unit);
        }

        return min($returnTimes);
    }


}
