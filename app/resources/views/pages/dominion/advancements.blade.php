@extends('layouts.master')

{{--
@section('page-header', 'Technological Advances')
--}}

@section('content')
    @php
      $unlockedTechs = $selectedDominion->techs->pluck('key')->all()
    @endphp

    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-flask"></i> Advancements</h3>
                    <a href="{{ route('dominion.mentor.advancements') }}" class="pull-right"><span><i class="ra ra-help"></i> Mentor</span></a>
                </div>
                <form action="{{ route('dominion.advancements') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <colgroup>
                                <col width="200">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Advancement</th>
                                    @for ($i = 1; $i <= 10; $i++)
                                        <th class="text-center">Level {{ $i }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            @foreach ($techs as $tech)
                                @if($tech->level == 1)
                                    <tr>
                                    <td>{{ $tech->name }}</td>
                                @else
                                @endif

                                <td class="text-center">
                                    <span data-toggle="tooltip" data-placement="top" title="<strong>{{$tech['name'] }} Level {{ $tech['level'] }}</strong><br>{{ $techHelper->getTechDescription($tech) }}<br>XP: {{ number_format($techCalculator->getTechCost($selectedDominion, null, $tech->level)) }}" style="display:block;">

                                @if(in_array($tech->key, $unlockedTechs))
                                    <i class="fa fa-check text-green"></i>
                                @elseif(count(array_diff($tech->prerequisites, $unlockedTechs)) == 0 and $techCalculator->canAffordTech($selectedDominion, $tech->level))
                                    <input type="radio" name="key" id="{{ $tech->key }}" value="{{ $tech->key }}">
                                @else
                                    <input type="radio" name="key" id="{{ $tech->key }}" value="{{ $tech->key }}" disabled>
                                @endif
                                    </span>
                                </td>

                                @if($tech->level == 10)
                                    </tr>
                                @endif
                            @endforeach

                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Level Up</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <h4>Cost</h4>
                    <ul>
                    @for ($i = 1; $i <= 10; $i++)
                        <li>Level {{ $i }}: {{ number_format($techCalculator->getTechCost($selectedDominion, null, $i)) }}</li>
                    @endfor
                    </ul>
                    <p>You have <b>{{ number_format($selectedDominion->resource_tech) }} XP</b>, which is increasing your ruler title bonus by {{ number_format(($selectedDominion->title->getPerkBonus($selectedDominion)-1)*100,2) }}%.</p>
                    <p>Only the perks from the highest-level advancement counts. if you have Level 1 and Level 2, only Level 2 counts.</p>

                    <a href="{{ route('scribes.advancements') }}"><span><i class="ra ra-scroll-unfurled"></i> Read more about Advancements in the Scribes.</span></a>
                </div>
            </div>
        </div>

    </div>
@endsection
