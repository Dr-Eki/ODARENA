<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services;

use OpenDominion\Calculators\Dominion\TradeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Resource;
use Illuminate\Http\JsonResponse;

class TradeCalculationService
{

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        'bought_resource_amount' => 0,
    ];

    protected $tradeCalculator;

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct()
    {
        $this->tradeCalculator = app(TradeCalculator::class);
    }

    /**
     * Calculates an expedition
     *
     * @param Dominion $dominion
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $dominion, int $holdId, string $soldResourceKey, int $soldResourceAmount, string $boughtResourceKey): JsonResponse
    {
        $hold = Hold::find($holdId);
        $soldResource = Resource::where('key', $soldResourceKey)->firstOrFail();
        $boughtResource = Resource::where('key', $boughtResourceKey)->firstOrFail();

        if ($soldResourceAmount <= 0) {
            return ['result' => 'error', 'message' => 'Invalid amount'];
        }

        if (!$this->tradeCalculator->canDominionTradeWithHold($dominion, $hold)) {
            return ['result' => 'error', 'message' => 'You cannot trade with this hold'];
        }

        if ($hold->round->id !== $dominion->round->id) {
            return ['result' => 'error', 'message' => 'Invalid input/selection'];
        }

        if ($soldResource->id == $boughtResource->id) {
            return ['result' => 'error', 'message' => 'You cannot trade the same resources'];
        }
        
        $tradeData = $this->tradeCalculator->getTradeResult($dominion, $hold, $soldResource, $soldResourceAmount, $boughtResource);

        $this->calculationResult['bought_resource_amount'] = $tradeData['bought_resource_amount'];

        return response()->json($this->calculationResult);
    }
}
