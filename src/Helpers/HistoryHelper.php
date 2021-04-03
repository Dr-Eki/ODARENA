<?php

namespace OpenDominion\Helpers;

use LogicException;

class HistoryHelper
{

    public function getEventIcon(string $event): string
    {
        $icons = [
          'bank' => 'fa fa-money fa-fw',
          'cast spell' => 'ra ra-fairy-wand ra-fw',
          'change draft rate' => 'ra ra-sword ra-fw',
          'construct' => 'fa fa-home fa-fw',
          'daily bonus' => 'fa fa-plus fa-fw',
          'destroy' => 'fa fa-home fa-fw',
          'explore' => 'ra ra-telescope ra-fw',
          'improve' => 'fa fa-arrow-up fa-fw',
          'invade' => 'ra ra-crossed-swords ra-fw',
          'perform espionage operation' => 'fa fa-user-secret fa-fw',
          'release' => 'ra ra-sword ra-fw',
          'rezone' => 'fa fa-refresh fa-fw',
          'tech' => 'fa fa-flask fa-fw',
          'tick' => 'ra ra-hourglass ra-fw',
          'train' => 'ra ra-sword ra-fw',
          'update notes' => 'ra ra-quill-ink ra-fw',
          'join warriors league' => 'ra ra-heavy-shield ra-fw',
        ];

        return $icons[$event];
    }

    public function getEventName(string $event): string
    {
        $name = [
          'bank' => 'Exchange',
          'cast spell' => 'Spell',
          'change draft rate' => 'Draft rate',
          'construct' => 'Construction',
          'daily bonus' => 'Daily bonus',
          'destroy' => 'Destroy',
          'explore' => 'Explore',
          'improve' => 'Improvement',
          'invade' => 'Invasion',
          'perform espionage operation' => 'Espionage',
          'release' => 'Release',
          'rezone' => 'Rezoning',
          'tech' => 'Advancement',
          'tick' => 'Tick',
          'train' => 'Training',
          'update notes' => 'Notes',
        ];

        return $name[$event];
    }

}
