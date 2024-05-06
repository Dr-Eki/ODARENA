@php
    $manaAfforded = $spellCalculator->getManaCost($selectedDominion, $spell->key) <= $selectedDominion->resource_mana ? 'text-green' : 'text-red';
@endphp

@if($spell->magic_level > 0)
    M: <span class="{{ $manaAfforded }}"  data-toggle="tooltip" data-placement="top" title="Mana cost to cast spell">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell->key)) }}</span> 
@endif

@if($spell->duration > 0 and $spell->class != 'active')
  <span data-toggle="tooltip" data-placement="top" title="Duration (ticks)">
        <i class="fas fa-hourglass-start"></i>:
        @if(isset($isActive) and $isActive)
            <span class="text-green">{{ number_format($spellCalculator->getSpellDuration($selectedDominion, $spell->key)) }}/{{ number_format($spell->duration) }}</span>
        @else
            <span class="text-muted">{{ number_format($spell->duration) }}</span>
        @endif
        ticks
    </span>
@endif
@if($spell->cooldown > 0)
  <span data-toggle="tooltip" data-placement="top" title="Cooldown until spell can be cast again (ticks)">
        <i class="fas fa-hourglass-end"></i>:
        @if($spellCalculator->isOnCooldown($selectedDominion, $spell))
            <span class="text-red">{{ number_format($spellCalculator->getSpellCooldown($selectedDominion, $spell)) }}/{{ number_format($spell->cooldown) }}</span>
        @else
            <span class="text-muted">{{ number_format($spell->cooldown) }}</span>
        @endif
        ticks
    </span>
@endif
<span data-toggle="tooltip" data-placement="top" title="Wizard strength required to cast spell">WS</span>: {{ $spellCalculator->getWizardStrengthCost($spell) }}%
