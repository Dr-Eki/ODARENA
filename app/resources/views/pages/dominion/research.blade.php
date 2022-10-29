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
                        <h3 class="box-title"><i class="fa fa-flask"></i> Research</h3>
                    </div>
                    <div class="box-body">
                        {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                        @php
                            $numOfCols = 4;
                            $rowCount = 0;
                            $bootstrapColWidth = 12 / $numOfCols;
                            $boxClass = 'box-info';

                            $level1 = $techs->where('level', 1);
                            $level2 = $techs->where('level', 2);
                            $level3 = $techs->where('level', 3);
                            $level4 = $techs->where('level', 4);
                            $level5 = $techs->where('level', 5);
                            $level6 = $techs->where('level', 6);

                        @endphp

                        <div class="row">
                            @foreach($level1 as $tech)
                                <div class="col-md-{{ $bootstrapColWidth }}">
                                    <div class="box {{ $boxClass }}">
                                        <div class="box-header with-border text-center">
                                            <h4 class="box-title">{{ $tech->name }}</h4>
                                        </div>
                                        <div class="box-body">
                                            {{-- 
                                            <div class="progress">
                                                @if(!$advancementCalculator->getCurrentLevel($selectedDominion, $advancement))
                                                    <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="{{ $advancementCalculator->getDominionMaxLevel($selectedDominion) }}">No level</div>
                                                @else
                                                    <div class="progress-bar label-success" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $maxLevel }}">Level {{ $currentLevel }} </div>
                                                    <div class="progress-bar label-warning" role="progressbar" style="width: {{ $remaining }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $maxLevel }}"></div>
                                                @endif
                                            </div>
                                            --}}

                                            <div class="text-center">
                                                <form action="{{ route('dominion.research')}}" method="post" role="form" id="research_form">
                                                    @csrf
                                                    <input type="hidden" id="tech_id" name="tech_id" value="{{ $tech->id }}" required>

                                                    <button type="submit"
                                                            class="btn btn-primary btn-block"
                                                            {{ ($selectedDominion->isLocked() or !$researchCalculator->canResearchTech($selectedDominion, $tech)) ? 'disabled' : null }}
                                                            id="invade-button">
                                                            @if($researchCalculator->hasTech($selectedDominion, $tech))
                                                                <i class="fas fa-check-circle"></i> Already researched
                                                            @elseif(!$researchCalculator->hasPrerequisites($selectedDominion, $tech))
                                                                <i class="fas fa-ban"></i> Missing prerequisites
                                                            @else
                                                                Begin Research
                                                            @endif
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <ul>
                                                @foreach($tech->perks as $perk)
                                                @php
                                                    $techPerkBase = $perk->pivot->value;

                                                    $spanClass = 'text-muted';

                                                    if($techPerkMultiplier = $selectedDominion->getAdvancementPerkMultiplier($perk->key))
                                                    {
                                                        $spanClass = '';
                                                    }
                                                @endphp

                                                <span class="{{ $spanClass }}" data-toggle="tooltip" data-placement="top" title="Base: {{ number_format($techPerkBase, 2) }}%">

                                                @if($techPerkMultiplier > 0)
                                                    +{{ number_format($techPerkMultiplier * 100, 2) }}%
                                                @else
                                                    {{ number_format($techPerkMultiplier * 100, 2) }}%
                                                @endif

                                                {{ $researchHelper->getTechPerkDescription($perk->key) }}<br></span>

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
                <p>Select research to begin researching.</p>
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
