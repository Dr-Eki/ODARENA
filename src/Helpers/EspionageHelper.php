<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class EspionageHelper
{
    public function getOperationInfo(string $operationKey): array
    {
        return $this->getOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->first();
    }

    public function isInfoGatheringOperation(string $operationKey): bool
    {
        return $this->getInfoGatheringOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isResourceTheftOperation(string $operationKey): bool
    {
        return $this->getResourceTheftOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isBlackOperation(string $operationKey): bool
    {
        return $this->getBlackOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isWarOperation(string $operationKey): bool
    {
        return $this->getWarOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function getOperations(): Collection
    {
        return $this->getInfoGatheringOperations()
            ->merge($this->getResourceTheftOperations())
            ->merge($this->getBlackOperations())
            ->merge($this->getWarOperations());
    }

    public function getInfoGatheringOperations(): Collection
    {
        return collect([
            [
                'name' => 'Barracks Spy',
                'description' => 'Reveal estimate units',
                'key' => 'barracks_spy',
            ],
            [
                'name' => 'Castle Spy',
                'description' => 'Reveal castle improvements',
                'key' => 'castle_spy',
            ],
            [
                'name' => 'Survey Dominion',
                'description' => 'Reveal buildings',
                'key' => 'survey_dominion',
            ],
            [
                'name' => 'Land Spy',
                'description' => 'Reveal land',
                'key' => 'land_spy',
            ],
        ]);
    }

    public function getResourceTheftOperations(): Collection
    {
        return collect([
            [
                'name' => 'Steal Platinum',
                'description' => 'Steal platinum from target',
                'key' => 'steal_platinum',
            ],
            [
                'name' => 'Steal Food',
                'description' => 'Steal food from target',
                'key' => 'steal_food',
            ],
            [
                'name' => 'Steal Lumber',
                'description' => 'Steal lumber from target',
                'key' => 'steal_lumber',
            ],
            [
                'name' => 'Steal Mana',
                'description' => 'Steal mana from target',
                'key' => 'steal_mana',
            ],
            [
                'name' => 'Steal Ore',
                'description' => 'Steal ore from target',
                'key' => 'steal_ore',
            ],
            [
                'name' => 'Steal Gems',
                'description' => 'Steal gems from target',
                'key' => 'steal_gems',
            ],
            [
                'name' => 'Abduct Draftees',
                'description' => 'Abduct draftees from target',
                'key' => 'abduct_draftees',
            ],
            [
                'name' => 'Abduct Peasants',
                'description' => 'Abduct peasants from target',
                'key' => 'abduct_peasants',
            ],
        ]);
    }

    public function getBlackOperations(): Collection
    {
        return collect([
            // assassinate draftees
        ]);
    }

    public function getWarOperations(): Collection
    {
        return collect([
            // assassinate wiards
            // magic snare
            // sabotage boats
        ]);
    }
}
