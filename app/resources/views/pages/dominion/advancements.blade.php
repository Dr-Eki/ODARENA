@extends ('layouts.master')
@section('title', 'Advancements')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">

        <!-- RESOURCE -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-layer-group"></i> Advancements</h3>
                    </div>
                    <div class="box-body">
                    {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                    @php
                        $numOfCols = 4;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                    @endphp

                    <div class="row">

                    @foreach($advancements as $advancement)
                        @php
                            $dominionAdvancement = OpenDominion\Models\DominionAdvancement::where('advancement_id', $advancement->id)->where('dominion_id', $selectedDominion->id)->first();
                            $currentLevel = $advancementCalculator->getCurrentLevel($selectedDominion, $advancement);
                            $maxLevel = $advancementCalculator->getDominionMaxLevel($selectedDominion);
                            $maxedOut = ($currentLevel >= $maxLevel);
                            $progress = $currentLevel / $maxLevel;
                            $remaining = 1-$progress;
                            $progress *= 100;
                            $remaining *= 100;

                            if($currentLevel)
                            {
                                $boxClass = 'box-warning';
                                if($currentLevel == $maxLevel)
                                {
                                    $boxClass = 'box-success';
                                }
                            }
                            else
                            {
                                $boxClass = '';
                            }

                        @endphp
                        <div class="col-md-{{ $bootstrapColWidth }}">
                            <div class="box {{ $boxClass }}">
                                <div class="box-header with-border text-center">
                                    <h4 class="box-title">{{ $advancement->name }}</h4>
                                </div>
                                <div class="box-body">
                                    <div class="progress">
                                        @if(!$advancementCalculator->getCurrentLevel($selectedDominion, $advancement))
                                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="{{ $advancementCalculator->getDominionMaxLevel($selectedDominion) }}">No level</div>
                                        @else
                                            <div class="progress-bar label-success" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $maxLevel }}">Level {{ $currentLevel }} </div>
                                            <div class="progress-bar label-warning" role="progressbar" style="width: {{ $remaining }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $maxLevel }}"></div>
                                        @endif
                                    </div>

                                    <div class="text-center">
                                        <form action="{{ route('dominion.advancements')}}" method="post" role="form" id="advancements_form">
                                            @csrf
                                            <input type="hidden" id="advancement_id" name="advancement_id" value="{{ $advancement->id }}" required>

                                            @if($advancementCalculator->canLevelUp($selectedDominion, $advancement) and $advancementCalculator->getCurrentLevel($selectedDominion, $advancement))
                                            <span data-toggle="tooltip" data-placement="top" title="<b>Next level:</b><br>
                                                                                                    @foreach($advancementCalculator->getNextLevelPerks($selectedDominion, $advancement) as $perkValue)
                                                                                                        {{ $perkValue }}<br>
                                                                                                    @endforeach
                                                                                                ">
                                            @endif
                                            <button type="submit"
                                                    class="btn btn-primary btn-block"
                                                    {{ ($selectedDominion->isLocked() or !$advancementCalculator->canLevelUp($selectedDominion, $advancement) or !$advancementCalculator->canAffordToLevelUpAdvancement($selectedDominion, $advancement)) ? 'disabled' : null }}
                                                    id="invade-button">
                                                    @if($maxedOut)
                                                        <i class="fas fa-check-circle"></i> Max level
                                                    @elseif(!$advancementCalculator->canAffordToLevelUpAdvancement($selectedDominion, $advancement))
                                                        <i class="fas fa-ban"></i> Not enough XP
                                                    @elseif($advancementCalculator->canLevelUp($selectedDominion, $advancement))
                                                        <i class="fas fa-arrow-up"></i> Level up
                                                    @else
                                                        Hmm, this shouldn't be happening
                                                    @endif
                                            </button>
                                            @if($advancementCalculator->canLevelUp($selectedDominion, $advancement) and $advancementCalculator->getCurrentLevel($selectedDominion, $advancement))
                                                </span>
                                            @endif
                                            @if($advancementCalculator->canLevelUp($selectedDominion, $advancement) or !$advancementCalculator->canAffordToLevelUpAdvancement($selectedDominion, $advancement))
                                                <small class="text-muted">{{ number_format($advancementCalculator->getLevelUpCost($selectedDominion, $dominionAdvancement)) }} XP required</small>
                                            @else
                                                &nbsp;
                                            @endif
                                        </form>
                                    </div>
                                    
                                    <ul>
                                        @foreach($advancement->perks as $perk)
                                        @php
                                            $advancementPerkBase = $selectedDominion->extractAdvancementPerkValues($perk->pivot->value);

                                            $spanClass = 'text-muted';

                                            if($advancementPerkMultiplier = $selectedDominion->getAdvancementPerkMultiplier($perk->key))
                                            {
                                                $spanClass = '';
                                            }
                                        @endphp

                                        <span class="{{ $spanClass }}" data-toggle="tooltip" data-placement="top" title="Base: {{ number_format($advancementPerkBase, 2) }}%">

                                        @if($advancementPerkMultiplier > 0)
                                            +{{ number_format($advancementPerkMultiplier * 100, 2) }}%
                                        @else
                                            {{ number_format($advancementPerkMultiplier * 100, 2) }}%
                                        @endif

                                         {{ $advancementHelper->getAdvancementPerkDescription($perk->key) }}<br></span>

                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        @php
                            $rowCount++;
                        @endphp

                        @if($rowCount % $numOfCols == 0)
                            </div><div class="row">
                        @endif

                    @endforeach
                    </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>Advancements are levelled up by spending XP.</p>
                <p>The higher the level, the higher the perk becomes.</p>
                <ul>
                    <li>Levels up to and including 6:<br><code>[Base Perk]*[Level]</code></li>
                    <li>Levels 7 through 10:<br><code>[Base Perk]*(((6-[Level])/2)+6)</code></li>
                </ul>
                <table class="table table-striped">
                    <colgroup>
                        <col width="20%">
                        <col width="40%">
                        <col width="40%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">Level</th>
                            <th class="text-center">XP Cost</th>
                            <th class="text-center">Ticks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($level = 1; $level <= $advancementCalculator->getDominionMaxLevel($selectedDominion); $level++)
                            <tr>
                                <td class="text-center">{{ $level }}</td>
                                <td class="text-center">{{ number_format($advancementCalculator->getLevelUpCost($selectedDominion, null, $level)) }}</td>
                                <td class="text-center">{{ max(0, ceil(($advancementCalculator->getLevelUpCost($selectedDominion, null, $level) - $selectedDominion->xp) / $productionCalculator->getXpGeneration($selectedDominion))) }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                <p>You have <b>{{ number_format($selectedDominion->xp) }} XP</b>, which is increasing your ruler title bonus by {{ number_format(($selectedDominion->getTitlePerkMultiplier()-1)*100,2) }}%.</p>
            </div>
            <div class="box-footer">
                <p><small class="text-muted"><em>The XP cost shown is the amount of XP required for levelling to the next level when an advancement is that level. For example, the cost shown for level 4 is the cost of levelling up from 4 to 5.</em></small><p>
            </div>
        </div>
    </div>

</div>

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/slider.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/plugins/bootstrap-slider/bootstrap-slider.js') }}"></script>
@endpush

@push('page-scripts')
    <script type="text/javascript">
    $("form").submit(function () {
        // prevent duplicate form submissions
        $(this).find(":submit").attr('disabled', 'disabled');
    });
    </script>
@endpush
