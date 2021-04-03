<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TickActionRequest extends AbstractDominionRequest
{
  /**
   * {@inheritdoc}
   */
  public function rules()
  {
      return [
          'returnTo' => 'required',
      ];
  }
}
