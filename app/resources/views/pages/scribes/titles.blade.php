@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Titles</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <p>Every Dominion Ruler must select a Ruler Title, which comes with perks.</p>
                    <p>The perk is increased based on how many unspent Experience Points you have.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Perks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($titles as $title)
                                <tr>
                                    <td>{{ $title['name'] }}</td>
                                    <td>
                                      <ul>
                                      @foreach ($title->perks as $perk)
                                          @php
                                              $perkDescription = $titleHelper->getPerkDescriptionHtmlWithValue($perk);
                                          @endphp
                                          <li>
                                              {!! $perkDescription['description'] !!} {!! $perkDescription['value']  !!}
                                          </li>
                                      @endforeach
                                      </ul>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
