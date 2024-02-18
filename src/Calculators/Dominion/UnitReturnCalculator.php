<?php

namespace OpenDominion\Calculators\Dominion;
use Illuminate\Support\Facades\Validator;
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
 
    public function getUnitReturnTicks(Dominion $dominion, Unit $unit, $eventType = 'invasion', array $units = []): int
    {
        $baseReturnTicks = $this->getUnitBaseReturnTicks($dominion, $unit, $eventType);
        $returnTicksMultiplier = $this->getUnitReturnTicksMultiplier($dominion);

        return max((int)floor($baseReturnTicks * $returnTicksMultiplier), 1);
    }

    public function getUnitBaseReturnTicks(Dominion $dominion, Unit $unit, string $eventType = 'invasion'): int
    {
        $ticks = config('game.defaults.unit_training_ticks');

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

    public function getUnitReturnTicksMultiplier(Dominion $dominion): float
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

    public function getSlowestUnitReturnTicks(Dominion $dominion, array $units): int
    {
        $returnTimes = [];

        foreach ($units as $unit) {
            $returnTimes[] = $this->getUnitReturnTicks($dominion, $unit);
        }

        return max($returnTimes);
    }

    public function getFastestUnitReturnTicks(Dominion $dominion, array $units): int
    {
        $returnTimes = [];

        foreach ($units as $unit) {
            $returnTimes[] = $this->getUnitReturnTicks($dominion, $unit);
        }

        return min($returnTimes);
    }

    public function getReturningUnitsArray(Dominion $dominion, array $units): array
    {

        $this->validateUnits($units);

        $returningUnits = [];

        foreach ($units as $unitsGroup)
        {
            foreach($unitsGroup as $unitSlot => $amount)
            {
                if(is_numeric($unitSlot))
                {
                    $unit = $dominion->race->units->where('slot', $unitSlot)->first();

                    $returningUnits[] = [
                        'slot' => $unitSlot,
                        'return_ticks' => $this->getUnitReturnTicks($dominion, $unit),
                    ];    
                }
                elseif(in_array($unitSlot, ['spies', 'wizards', 'archmages', 'draftees', 'peasants']))
                {
                    $returningUnits[] = [
                        'unit' => $unitSlot,
                        'return_ticks' => 12,
                    ];   
                }
            }
        }

        return $returningUnits;
    }

    public function validateUnits(array $units): void
    {
        $validator = Validator::make($units, [
            'survivors' => 'required|array',
            'converted' => 'nullable|array',
            'survivors.*' => 'integer|min:0',
            'converted.*' => 'integer|min:0',
        ]);
    
        $validator->sometimes(['survivors.spies', 'survivors.wizards', 'survivors.archmages', 'survivors.draftees', 'survivors.peasants'], 'required|integer|min:0', function ($input) {
            return array_key_exists('spies', $input['survivors']) || array_key_exists('wizards', $input['survivors']) || array_key_exists('archmages', $input['survivors']) || array_key_exists('draftees', $input['survivors']) || array_key_exists('peasants', $input['survivors']);
        });
    
        $validator->sometimes(['converted.spies', 'converted.wizards', 'converted.archmages', 'converted.draftees', 'converted.peasants'], 'nullable|integer|min:0', function ($input) {
            return isset($input['converted']) && (array_key_exists('spies', $input['converted']) || array_key_exists('wizards', $input['converted']) || array_key_exists('archmages', $input['converted']) || array_key_exists('draftees', $input['converted']) || array_key_exists('peasants', $input['converted']));
        });
    
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

}
