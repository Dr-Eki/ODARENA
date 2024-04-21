<?php

namespace OpenDominion\Helpers;

class HoldHelper
{


    public function __construct()
    {
    }

    public function getSentimentDescription(int $sentiment): string
    {


        if($sentiment < -60)
        {
            return 'despise';
        }
        if($sentiment < -40)
        {
            return 'hate';
        }
        if($sentiment < -20)
        {
            return 'dislike';
        }
        if($sentiment < -10)
        {
            return 'avoidant';
        }
        if($sentiment < 0)
        {
            return 'hesitant';
        }
        if($sentiment == 0)
        {
            return 'neutral';
        }
        if($sentiment < 10)
        {
            return 'curious';
        }
        if($sentiment < 100)
        {
            return 'respect';
        }
        if($sentiment < 500)
        {
            return 'like';
        }
        if($sentiment < 750)
        {
            return 'admire';
        }
        if($sentiment < 1000)
        {
            return 'fond';
        }
        if($sentiment >= 1000)
        {
            return 'adore';
        }

        return 'undefined';
    }
    
    public function getSentimentClass(string $description): string
    {
        $classes = [
            'despise' =>    'danger',
            'hate' =>       'danger',
            'dislike' =>    'danger',
            'avoidant' =>   'warning',
            'hesitant' =>   'warning',
            'neutral' =>    'primary',
            'curious' =>    'info',
            'respect' =>    'info',
            'like' =>       'success',
            'admire' =>     'success',
            'adore' =>      'success',
        ];
    
        return $classes[$description] ?? '';
    }
    
    

}
