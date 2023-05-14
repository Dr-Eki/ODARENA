<?php

namespace OpenDominion\Services\Dominion\API;

use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;

class SorceryCalculationService
{

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'mana_cost' => 0,
    ];

    /** @var SorceryCalculator */
    protected $sorceryCalculator;

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct()
    {
        $this->sorceryCalculator = app(SorceryCalculator::class);
    }

    /**
     * Calculates an expedition
     *
     * @param Dominion $dominion
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $caster, Spell $spell, int $wizardStrength): array
    {
        $this->calculationResult['mana_cost'] = $this->sorceryCalculator->getSpellManaCost($caster, $spell, $wizardStrength);

        return $this->calculationResult;
    }
}
