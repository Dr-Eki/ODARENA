<?php

namespace OpenDominion\Helpers;

# ODA
use OpenDominion\Models\Dominion;

class OpsHelper
{
    /*
    public function operationSuccessChance(float $selfRatio, float $targetRatio, float $multiplier, bool $isInvasionSpell = false): float
    {
        if($isInvasionSpell)
        {
          return 1;
        }
        $ratio = $selfRatio / $targetRatio;
        $successRate = 0.8 ** (2 / (($ratio * $multiplier) ** 1.2));
        return clamp($successRate, 0, 1);
    }
    */

    # RVV DID THIS:
    /**
    * Error function (also called the Gauss error function)
    * Value is computed using
    * "Numerical Recipes in Fortran 77: The Art of Scientific Computing"
    * (ISBN 0-521-43064-X), 1992, page 214, Cambridge University Press.
    * Maximum error is 1.2 * 10^-7
    *
    * https://en.wikipedia.org/wiki/Error_function
    *
    * @param float $x
    * @return float
    */
    public function erf($x){
        $t =1 / (1 + 0.5 * abs($x));
        $tau = $t * exp(
            - $x * $x
            - 1.26551223
            + 1.00002368 * $t
            + 0.37409196 * $t * $t
            + 0.09678418 * $t * $t * $t
            - 0.18628806 * $t * $t * $t * $t
            + 0.27886807 * $t * $t * $t * $t * $t
            - 1.13520398 * $t * $t * $t * $t * $t * $t
            + 1.48851587 * $t * $t * $t * $t * $t * $t * $t
            - 0.82215223 * $t * $t * $t * $t * $t * $t * $t * $t
            + 0.17087277 * $t * $t * $t * $t * $t * $t * $t * $t * $t);

        if ($x >= 0) {
            return 1 - $tau;
        } else {
            return $tau - 1;
        }
    }

    public function operationSuccessChance(float $selfRatio, float $targetRatio, float $multiplier, bool $isInvasionSpell = false): float
    {
        if ($isInvasionSpell)
        {
            return 1;
        }

        $ratio = $selfRatio / $targetRatio;
        $successRate = 0.8 ** (2 / (($ratio * $multiplier) ** 1.2));
        return clamp($successRate, 0, 1);
    }

    public function blackOperationSuccessChance(float $selfRatio, float $targetRatio, bool $isInvasionSpell = false): float
    {
        if ($isInvasionSpell)
        {
            return 1;
        }

        #$selfRatio = min(5, $selfRatio);
        #$targetRatio = min(5, $targetRatio);

        $ratioDifference = $selfRatio - $targetRatio;
        $ratioSum = $selfRatio + $targetRatio;

        $steepness = 1 / (2 + sqrt($ratioSum)/2);
        $shift = 0;
        $slimChance = 0.02;

        $successRate = ($this->erf(($ratioDifference - $shift) * $steepness) + 1) * (0.5 - $slimChance) + $slimChance;

        return clamp($successRate, 0, 1);
    }

      public function getInfoOpsInaccuracy(Dominion $dominion): float
      {

          $obfuscatingUnits = 0;
          $totalUnitsAtHome = $dominion->military_unit1 + $dominion->military_unit2 + $dominion->military_unit3 + $dominion->military_unit4;
          $inaccuracy = 0;

          for ($slot = 1; $slot <= 4; $slot++)
          {
              if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'decreases_info_ops_accuracy'))
              {
                  $obfuscatingUnits += $dominion->{'military_unit'.$slot};
              }
          }

          if($obfuscatingUnits !== 0)
          {
              $inaccuracy = ($obfuscatingUnits / $totalUnitsAtHome)/2;
          }

          return $inaccuracy;
      }

      public function getInfoOpsAccuracyModifier(Dominion $dominion): float
      {

          if($opsInaccuracy = $this->getInfoOpsInaccuracy($dominion))
          {
              return (rand(1000 * (1-$opsInaccuracy), 1000 / (1-$opsInaccuracy)))/1000;
          }

          return 1;
      }

      public function isHallucination(Dominion $dominion): bool
      {
          if($dominion->race->name !== 'Myconid')
          {
              return false;
          }

          return false;
      }

}
