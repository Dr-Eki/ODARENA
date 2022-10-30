@extends ('layouts.master')
@section('title', 'Research')

@section('content')
<div class="row">
    <div class="col-sm-12 col-md-9">

        <div class="row">
            <div class="col-md-12">
                @php
                    $techsWithLevel = [];

                    for ($level = 1; $level <= 6; $level++)
                    {
                        $techsWithLevel[$level] = $techs->filter(function ($tech) use ($level) {
                            return $tech->level === $level;
                        });
                    }

                    $researchTime = $researchCalculator->getResearchTime($selectedDominion);

                @endphp

                @foreach($techsWithLevel as $level => $levelTechs)
                    {{-- //Columns must be a factor of 12 (1,2,3,4,6,12) --}}
                    @php
                        $numOfCols = 4;
                        $rowCount = 0;
                        $bootstrapColWidth = 12 / $numOfCols;
                        $boxClass = 'box-info';
                    @endphp
                    <div class="box box-body">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-flask"></i> Tier {{ $level }}</h3>
                        </div>
                        <div class="row">
                            @foreach($levelTechs as $tech)
                                @php
                                    $buttonClass = 'btn-primary';
                                    $boxClass = '';

                                    if($researchCalculator->isBeingResearched($selectedDominion, $tech))
                                    {
                                        $buttonClass = 'btn-warning';

                                        $ticksRemaining = $researchCalculator->getTicksRemainingOfResearch($selectedDominion, $tech);
                                        #$progress = ($researchTime - $ticksRemaining) / $researchTime;
                                        $progress = (96 - $ticksRemaining) / 96;
                                        $remaining = 1-$progress;

                                        $progress *= 100;
                                        $remaining *= 100;
                                    }

                                    if($researchCalculator->hasTech($selectedDominion, $tech))
                                    {
                                        $buttonClass = 'btn-success';
                                        $boxClass = 'box-success';
                                    }

                                    $techsRequired = $researchCalculator->getTechsRequired($tech);
                                    $techsLeadTo = $researchCalculator->getTechsLeadTo($tech);

                                    if($techsRequired->count() == 0)
                                    {
                                        $boxClass = 'box-success';
                                    }
                                        
                                    if($researchCalculator->isBeingResearched($selectedDominion, $tech))
                                    {
                                        $boxClass = 'box-warning';
                                    }

                                    foreach($techsRequired as $index => $techRequired)
                                    {
                                        if(!$researchHelper->getTechsByRace($selectedDominion->race)->contains($techRequired))
                                        {
                                            $techsRequired->forget($index);
                                        }

                                        if($researchCalculator->hasTech($selectedDominion, $techRequired))
                                        {
                                            $boxClass = 'box-success';
                                        }
                                        
                                        if($researchCalculator->isBeingResearched($selectedDominion, $techRequired))
                                        {
                                            $boxClass = 'box-warning';
                                        }
                                    }

                                    foreach($techsLeadTo as $index => $techLeadTo)
                                    {
                                        if(!$researchHelper->getTechsByRace($selectedDominion->race)->contains($techLeadTo))
                                        {
                                            $techsLeadTo->forget($index);
                                        }

                                        if($researchCalculator->hasTech($selectedDominion, $techLeadTo))
                                        {
                                            $boxClass = 'box-success';
                                        }
                                        
                                        if($researchCalculator->isBeingResearched($selectedDominion, $techLeadTo))
                                        {
                                            $boxClass = 'box-warning';
                                        }
                                    }

                                @endphp
                                <a id="{{ $tech->name }}"></a>
                                <div class="col-md-{{ $bootstrapColWidth }}">
                                    <div class="box {{ $boxClass }}">
                                        <div class="box-header with-border text-center">
                                            <h4 class="box-title">{{ $tech->name }}</h4>
                                        </div>
                                        <div class="box-body">
                                            
                                            <div class="progress">
                                                @if($researchCalculator->hasTech($selectedDominion, $tech))
                                                    <div class="progress-bar progress-bar-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="{{ $researchTime }}">Complete</div>
                                                @else
                                                    @if(!$researchCalculator->isBeingResearched($selectedDominion, $tech))
                                                        <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="{{ $researchTime }}">Not researched</div>
                                                    @else
                                                        <div class="progress-bar label-success" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $researchTime }}">{{ $ticksRemaining . ' ' .str_plural('tick', $ticksRemaining)}} remaining</div>
                                                        <div class="progress-bar label-warning" role="progressbar" style="width: {{ $remaining }}%" aria-valuenow="25" aria-valuemin="25" aria-valuemax="{{ $researchTime }}"></div>
                                                    @endif
                                                @endif
                                            </div>

                                            <div class="text-center">
                                                <form action="{{ route('dominion.research')}}" method="post" role="form" id="research_form">
                                                    @csrf
                                                    <input type="hidden" id="tech_id" name="tech_id" value="{{ $tech->id }}" required>

                                                    <button type="submit"
                                                            class="btn {{ $buttonClass }} btn-block"
                                                            {{ ($selectedDominion->isLocked() or !$researchCalculator->canResearchTech($selectedDominion, $tech)) ? 'disabled' : null }}
                                                            id="invade-button">
                                                            @if($researchCalculator->hasTech($selectedDominion, $tech))
                                                                <i class="fas fa-check-circle"></i> Researched
                                                            @elseif($researchCalculator->isBeingResearched($selectedDominion, $tech))
                                                                <i class="fas fa-hourglass-half"></i> Researching
                                                            @elseif(!$researchCalculator->hasPrerequisites($selectedDominion, $tech))
                                                                <i class="fas fa-ban"></i> Missing prerequisites
                                                            @elseif(!$researchCalculator->getFreeResearchSlots($selectedDominion))
                                                                <i class="fas fa-ban"></i> No research slots available
                                                            @else
                                                                Begin Research
                                                            @endif
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="box-header with-border text-center">
                                                        <b>Perks</b>
                                                    </div>
                                                    <div class="box-body text-center">
                                                        @foreach($researchHelper->getTechPerkDescription($tech, $selectedDominion->race) as $effect)
                                                            @if($effect == 'None')
                                                                <em class="text-muted">None</em>
                                                            @else
                                                                {{ $effect }}<br>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="box-header with-border text-center">
                                                        <b>Requires</b>
                                                    </div>
                                                    <div class="box-body no-border text-center">
                                                        <span class="text-muted">
                                                            @if($techsRequired->count() > 0)
                                                                @foreach($techsRequired as $techRequired)
                                                                    <a href="#{{ $techRequired->name }}">{{ $techRequired->name }}</a><br>
                                                                @endforeach
                                                            @else
                                                                <em>None</em>
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="col-sm-6">
                                                    <div class="box-header with-border text-center">
                                                        <b>Leads To</b>
                                                    </div>
                                                    <div class="box-body no-border text-center">
                                                        <span class="text-muted">
                                                            @if($techsLeadTo->count() > 0)
                                                                @foreach($techsLeadTo as $techLeadTo)
                                                                    <a href="#{{ $techLeadTo->name }}">{{ $techLeadTo->name }}</a><br>
                                                                @endforeach
                                                            @else
                                                                <em>None</em>
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
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
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-3">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Information</h3>
            </div>
            <div class="box-body">
                <p>You have {{ $researchCalculator->getFreeResearchSlots($selectedDominion) }} of {{ $researchCalculator->getResearchSlots($selectedDominion) }} research {{ str_plural('slot', $researchCalculator->getFreeResearchSlots($selectedDominion)) }} available.</p>
                <p>A new research project will take {{ number_format($researchCalculator->getResearchTime($selectedDominion)) }} ticks to complete.</p>
                <p>If you have multiple technologies with the same perk, only the highest perk will apply.</p>
                <p><span class="label label-warning">&nbsp;</span> Orange means currently being researched, or that a prerequisite is being researched.</p>
                <p><span class="label label-success">&nbsp;</span> Green means researched or available for research (when you have a research slot free).</p>
                <p><span class="label" style="background: #ccc;">&nbsp;</span> Gray means not currently researched and you lack one or more prerequisites.</p>
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
