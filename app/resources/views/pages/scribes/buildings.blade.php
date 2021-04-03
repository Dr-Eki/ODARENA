@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Buildings</h3>
            <span class="label label-danger pull-right">Beta</span></a>
        </div>
        <div class="box-body">
            <div class="row">
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Buildings</h3>
        </div>

        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                  <table class="table table-striped">
                      <colgroup>
                          <col width="200">
                          <col width="50">
                      </colgroup>
                      <thead>
                          <tr>
                              <th>Building</th>
                              <th>Land Type</th>
                              <th>Perks</th>
                          </tr>
                      </thead>
                      @foreach ($buildings as $building)
                          <tr>
                              <td>
                                  {{ $building->name }}
                                  {!! $buildingHelper->getExclusivityString($building) !!}
                              </td>
                              <td>{{ ucwords($building->land_type) }}</td>
                              <td>
                                  {!! $buildingHelper->getBuildingDescription($building) !!}
                              </td>
                          </tr>
                      @endforeach
                  </table>
                </div>
            </div>
        </div>
    </div>
@endsection
