@extends('layouts.topnav')
@section('title', "Scribes | Artefacts")

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Artefacts</h3>
        </div>
        <div class="box-body">
        </div>
    </div>

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Artefacts</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                          <col width="100">
                          <col>
                          <col>
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Artefact</th>
                              <th>Aegis</th>
                              <th>Divine</th>
                              <th>Perks</th>
                          </tr>
                      </thead>
                      <tbody>
                      @foreach ($artefacts as $artefact)
                          <tr>
                              <td>
                                  {{ $artefact->name }}
                                  {{-- {!! $artefactHelper->getExclusivityString($artefact) !!} --}}
                              </td>
                              <td>{{ number_format($artefact->base_power) }}</td>
                              <td>
                                  @if($artefact->deity)
                                      {{ $artefact->deity->name }}
                                  @else
                                      <em class="text-muted">No</em>
                                  @endif
                              </td>
                              <td>
                                  <ul>
                                      @foreach($artefactHelper->getArtefactPerksString($artefact) as $effect)
                                          <li>{{ ucfirst($effect) }}</li>
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
