<div class="row">
    <div class="col-sm-12">
        <small class="text-muted">Cost:</small> <span class="text-primary" data-toggle="tooltip" data-placement="top" title="Base mana cost">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell->key)) }}</span> <small class="text-muted">per 1% WS</small>
    </div>

    @if($spell->class == 'passive')
        <div class="col-sm-12">
            <small class="text-muted">Duration:</small> <span class="text-primary" data-toggle="tooltip" data-placement="top" title="Base duration">{{ floatval($spell->duration) . ' ' . Str::plural('tick', $spell->duration)}}</span> <small class="text-muted">(base)</small>
        </div>
    @elseif($spell->class == 'active')
        <div class="col-sm-12">
            <small class="text-muted">Duration:</small> <span class="text-muted" class="text-primary" data-toggle="tooltip" data-placement="top" title="This is an impact spell with immediate damage and no lingering effect">Immediate</span>
        </div>
    @endif

    @if($spell->cooldown > 0)
        @if($spellCalculator->isOnCooldown($selectedDominion, $spell))
            <div class="col-sm-12">
                <small class="text-muted">Cooldown:</small> <span class="text-red">{{ number_format($spellCalculator->getSpellCooldown($selectedDominion, $spell)) }}/{{ number_format($spell->cooldown) }}</span>
            </div>
        @else
            <div class="col-sm-12">
                <small class="text-muted">Cooldown:</small> <span class="text-info" data-toggle="tooltip" data-placement="top" title="Cooldown until spell can be cast again">{{ number_format($spell->cooldown) . ' ' . Str::plural('tick', $spell->cooldown)}}</span>
            </div>
        @endif
    @endif
</div>
