<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class NameChangeActionRequest extends AbstractDominionRequest
{
  /**
   * {@inheritdoc}
   */
  public function rules()
  {
      return [
        'dominion_name' => 'required|string|min:3|max:50',
      ];
  }
}
