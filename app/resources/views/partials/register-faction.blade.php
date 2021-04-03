<div class="col-xs-12">
    <label class="btn btn-block" style="border: 1px solid #d2d6de; margin: 5px 0px; white-space: normal;">
        <div class="row text-left">
            <div class="col-lg-2">
                <p>
                    @if($roundsPlayed < $race->getPerkValue('min_rounds_played'))
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
                      (Max {{ $race->getPerkValue('max_per_round') }} per round)
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
                            Skill level:
                        </td>
                        <td>
                            @if($race->skill_level === 1)
                                <span class="label label-success">Beginner</span>
                            @elseif($race->skill_level === 2)
                                <span class="label label-warning">Intermediate</span>
                            @elseif($race->skill_level === 3)
                                <span class="label label-danger">Advanced</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Attacking:
                        </td>
                        <td>
                            @if($race->attacking === 0)
                                <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->attacking === 1)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->attacking === 2)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->attacking === 3)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Converting:
                        </td>
                        <td>
                            @if($race->converting === 0)
                                <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->converting === 1)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->converting === 2)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->converting === 3)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Exploring:
                        </td>
                        <td>
                            @if($race->exploring === 0)
                                <i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->exploring === 1)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->exploring === 2)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-gray"></i>
                            @elseif($race->exploring === 3)
                                <i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i><i class="fa fa-star text-yellow"></i>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if($race->getPerkValue('min_rounds_played') > 0)
            <div class="col-sm-4">
                  <p><em>You must have played at least {{ number_format($race->getPerkValue('min_rounds_played')) }} rounds to play {{ $race->name }}.</em></p>
            </div>
            @endif
        </div>
    </label>
</div>
