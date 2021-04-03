<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class NotesActionRequest extends AbstractDominionRequest
{
  /**
   * {@inheritdoc}
   */
  public function rules()
  {
      return [
          'notes' => 'required|max:10000|string|nullable',
      ];
  }
}
