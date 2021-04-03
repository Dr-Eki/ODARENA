@php
    $manaAfforded = $spellCalculator->getManaCost($selectedDominion, $spell->key) <= $selectedDominion->resource_mana ? 'text-green' : 'text-red';
@endphp

<span data-toggle="tooltip" data-placement="top" title="Mana required to cast spell">M</span>: <span class="{{ $manaAfforded }}">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell->key)) }}</span>
@if($spell->duration > 0)
  / <span data-toggle="tooltip" data-placement="top" title="Tick duration of effect">{{--T--}}<i class="ra ra-hourglass"></i></span>:
        @if(isset($isActive) and $isActive)
            <span class="text-green">{{ number_format($spellCalculator->getSpellDuration($selectedDominion, $spell->key)) }}/{{ number_format($spell->duration) }}</span>
        @else
            <span class="text-muted">{{ number_format($spell->duration) }}</span>
        @endif
         ticks
@endif
@if($spell->cooldown > 0)
  / <span data-toggle="tooltip" data-placement="top" title="Cooldown until spell can be cast again">CD</span>:
        @if($spellCalculator->isOnCooldown($selectedDominion, $spell))
            <span class="text-red">{{ number_format($spellCalculator->getSpellCooldown($selectedDominion, $spell)) }}/{{ number_format($spell->cooldown) }}</span>
        @else
            <span class="text-muted">{{ number_format($spell->cooldown) }}</span>
        @endif
        h
@endif
@if($spell->wizard_strength)
  / <span data-toggle="tooltip" data-placement="top" title="Wizard stregth required to cast spell">WS</span>: {{ $spell->wizard_strength }}%
@endif
