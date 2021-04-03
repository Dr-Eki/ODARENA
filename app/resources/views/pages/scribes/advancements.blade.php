@extends('layouts.topnav')

@section('content')
@include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Advancements</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Advancements give benefits to your dominion. There are {{ count($techNames) }} Advancements, each one having 10 levels.</p>
                    <p>You level up Advancements by spending XP, which is generated each tick based on your prestige. You can also earn XP from invasions, exploring, and by successuflly performing hostile spy ops and casting hostile spells on other dominions.</p>
                    <p>The cost of levelling up Advancements is based on your land size. For every level, the cost is increased by 10%.</p>
                    @foreach($techNames as $techName)
                        <a href="#{{ $techName }}">{{ $techName }}</a> |
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    @foreach ($techs as $tech)
        @if($tech->level == 1)
        <a id="{{ $tech->name }}"></a>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $tech->name }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table">
                    <tr>
        @endif

        <td>
            <strong>Level {{ $tech->level }}</strong><br>
            {{ $techHelper->getTechDescription($tech) }}
        </td>

        @if($tech->level == 10)
                    </tr>
                </table>
            </div>
        </div>
        @endif
    @endforeach

    {{--
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Advancements</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
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
                          @endif

                          <td class="text-center">
                            {{ $techHelper->getTechDescription($tech) }}
                          </td>

                          @if($tech->level == 10)
                              </tr>
                          @endif
                      @endforeach

                  </table>
                </div>
            </div>
        </div>
    </div>
    --}}
@endsection
