<div class="col-xs-12">
    <label class="btn btn-block" style="border: 1px solid #d2d6de; margin: 5px 0px; white-space: normal;">
        <div class="row text-left">
            <div class="col-lg-2">
                <p>
                    @if($roundsPlayed < $race->minimum_rounds)
                        <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off" {{ (old('race') == $race->id) ? 'checked' : null }}>
                    @else
                        <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off" {{ (old('race') == $race->id) ? 'checked' : null }} required>
                    @endif
                    <strong>{{ $race->name }}</strong>
                    &nbsp;&mdash;&nbsp;
                <a href="{{ route('scribes.faction', $race->name) }}">Scribes</a>



                </p>
                <ul>
                  <li>Currently:&nbsp;
                  @if(isset($countRaces[$race->name]))
                      {{ number_format($countRaces[$race->name]) }}
                  @else
                      0
                  @endif
                  @if($race->getPerkValue('max_per_round') > 0)
                      (Max {{ number_format($race->getPerkValue('max_per_round')) }} per round)
                  @endif
                  </li>
                </ul>
            </div>

            <div class="col-sm-3">
                <table class="table table-condensed">
                    <colgroup>
                        <col width="25%">
                    </colgroup>
                    <tr>
                        <td>
                            Difficulty:
                        </td>
                        <td>
                            @if($race->skill_level === 1)
                                <span class="label label-success">Comfortable</span>
                            @elseif($race->skill_level === 2)
                                <span class="label label-warning">Challenging</span>
                            @elseif($race->skill_level === 3)
                                <span class="label label-danger">Advanced</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if($race->minimum_rounds)
            <div class="col-sm-4">
                  <p><em>You must have played at least {{ number_format($race->minimum_rounds) }} rounds to play {{ $race->name }}.</em></p>
            </div>
            @endif
        </div>
    </label>
</div>
