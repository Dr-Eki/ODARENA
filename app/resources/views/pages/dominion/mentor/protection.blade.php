@extends('layouts.master')

@section('page-header', 'Military Mentor')

@section('content')
    @include('partials.dominion.mentor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Protection</h3>
                </div>


                <div class="box-body">
                    <h4>General</h4>
                    <p>When you first create a dominion, you are given 84 protection ticks.</p>

                    <h4>Specific for {{ $selectedDominion->race->name }}</h4>

                    @if($selectedDominion->race->name === 'Growth')

                    @else
                        <p class="text-muted"><i>Not yet written. Want to contribute? Contact Dreki on <a href="{{ config('app.discord_invite_link') }}" target="_blank">Discord</a>.</i></p>
                    @endif

                </div>

            </div>
        </div>

        <div class="col-sm-12 col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Your Current Military</h3>
                </div>


                <div class="box-body">
                <table class="table table-striped">
                    <colgroup>
                        <col width="150">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Home</th>
                            <th>Away</th>
                            <th>Training</th>
                        </tr>
                    </thead>
                    @for ($slot = 1; $slot <= 4; $slot++)
                        @php
                            $unitType = 'unit'.$slot;
                        @endphp
                        <tr>
                            <td>{{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}</td>
                            <td>{{ number_format($selectedDominion->{'military_'.$unitType}) }}</td>
                            <td>{{number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_{$unitType}"))}}</td>
                            <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}"))}}</td>
                        </tr>
                    @endfor
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                    <tr>
                        <td>Spies</td>
                        <td>{{ number_format($selectedDominion->military_spies) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_spies"))}}</td>
                    </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                    <tr>
                        <td>Wizards</td>
                        <td>{{ number_format($selectedDominion->military_wizards) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_wizards"))}}</td>
                    </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                    <tr>
                        <td>Archmages</td>
                        <td>{{ number_format($selectedDominion->military_archmages) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_archmages"))}}</td>
                    </tr>
                    @endif
                    <tbody>
                    </tbody>
                </table>

                </div>

            </div>
        </div>

    </div>
@endsection
