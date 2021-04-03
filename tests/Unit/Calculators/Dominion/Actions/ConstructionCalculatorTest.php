<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ConstructionCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|ConstructionCalculator */
    protected $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->sut = m::mock(ConstructionCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
        ])->makePartial();
    }

    public function testGetGoldCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedGoldCost' => 850],
            ['totalBuildings' => 2000, 'totalLand' => 2000, 'expectedGoldCost' => 3528],
            ['totalBuildings' => 4000, 'totalLand' => 4000, 'expectedGoldCost' => 6588],
            ['totalBuildings' => 6000, 'totalLand' => 6000, 'expectedGoldCost' => 9648],
            ['totalBuildings' => 8000, 'totalLand' => 8000, 'expectedGoldCost' => 12708],
        ];

        $this->sut->shouldReceive('getGoldCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedGoldCost'], $this->sut->getGoldCost($this->dominionMock));
        }
    }

    public function testGetLumberCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedLumberCost' => 88],
            ['totalBuildings' => 2000, 'totalLand' => 2000, 'expectedLumberCost' => 700],
            ['totalBuildings' => 4000, 'totalLand' => 4000, 'expectedLumberCost' => 1400],
            ['totalBuildings' => 6000, 'totalLand' => 6000, 'expectedLumberCost' => 2100],
            ['totalBuildings' => 8000, 'totalLand' => 8000, 'expectedLumberCost' => 2800],
        ];

        $this->sut->shouldReceive('getLumberCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedLumberCost'], $this->sut->getLumberCost($this->dominionMock));
        }
    }

    /**
     * @dataProvider getGetMaxAffordProvider
     */
    public function testGetMaxAfford(
        /** @noinspection PhpDocSignatureInspection */
        int $totalBuildings,
        int $totalLand,
        int $totalBarrenLand,
        int $gold,
        int $lumber,
        int $discountedLand,
        int $expectedMaxAfford
    ) {
        $this->dominionMock->shouldReceive('getAttribute')->with('resource_gold')->andReturn($gold)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn($lumber)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('discounted_land')->andReturn($discountedLand)->byDefault();

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalBuildings)->byDefault();

        $this->landCalculator->shouldReceive('getTotalBarrenLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalBarrenLand)->byDefault();
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalLand)->byDefault();

        $this->sut->shouldReceive('getGoldCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);
        $this->sut->shouldReceive('getLumberCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        $this->assertEquals($expectedMaxAfford, $this->sut->getMaxAfford($this->dominionMock));

    }

    public function getGetMaxAffordProvider()
    {
        return [
            [ // new dominion
                'totalBuildings' => 90,
                'totalLand' => 250,
                'totalBarrenLand' => 160,
                'gold' => 100000,
                'lumber' => 15000,
                'discounted_land' => 0,
                'expectedMaxAfford' => 117,
            ],
            [
                'totalBuildings' => 2000,
                'totalLand' => 5000,
                'totalBarrenLand' => 3000,
                'gold' => 1000000,
                'lumber' => 150000,
                'discounted_land' => 0,
                'expectedMaxAfford' => 114,
            ],
            [
                'totalBuildings' => 4000,
                'totalLand' => 8000,
                'totalBarrenLand' => 4000,
                'gold' => 10000000,
                'lumber' => 1500000,
                'discounted_land' => 0,
                'expectedMaxAfford' => 714,
            ],
            // todo: add test case for discounted_land
        ];
    }
}
