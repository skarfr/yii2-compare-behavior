<?php
namespace app\components;
use yii\base\Behavior;


class CompareBehavior extends Behavior
{
  /**
   * List all available trends
   */
  const TREND_UP    = 'up';
  const TREND_DOWN  = 'down';
  const TREND_EQUAL = 'equal';

  /**
   * List related glyphicon CSS classes
   */
  const CLASS_UP    = 'glyphicon glyphicon-chevron-up trend-icon trend-up';
  const CLASS_DOWN  = 'glyphicon glyphicon-chevron-down trend-icon trend-down';
  const CLASS_EQUAL = 'glyphicon glyphicon-minus trend-icon trend-equal';

  /**
   * Compare fields/properties values of the calling object versus an other object: the reference 
   *
   * @param      var      $reference  Object which should be the same type as the calling object, to be compared to
   * @param      array    $fields     List of fields/properties names belonging to both the calling object and the reference one, to be compared. If null, it will compare all of them
   * @param      integer  $coeff      Coefficient applied to numeric fields/properties belonging to the reference, before it gets compared to the calling object. Default value: 1 (aka no coefficient)
   *
   * @return     array
   */
  public function compare($reference, $fields = null, $coeff = 1) {
    $differences = array();
    if(is_null($fields)) { // no fields name list given? we compare all of them!
      foreach($this->owner->attributes as $key => $refValue)
        $differences[$key] = $this->compareField($reference, $key, $coeff);
    }
    elseif(is_array($fields)) {
      foreach($fields as $fieldName)
        $differences[$fieldName] = $this->compareField($reference, $fieldName, $coeff);
    }
    return $differences;
  } 
 
 /**
  * Compare 1 field of the calling object versus the reference object same field
  *
  * @param      var      $reference  Object which should be the same type as the calling object, to be compared to
  * @param      string   $field      field/property name belonging to both the calling object and the reference one, to be compared
  * @param      integer  $coeff      Coefficient applied to numeric fields/properties belonging to the reference, before it gets compared to the calling object. Default value: 1 (aka no coefficient)
  */
  public function compareField($reference, $field, $coeff) {
    if(!isset($this->owner->$field)) { return null; } // the calling object doesn't have the reference: there is nothing to compare
    else {
      /**
       * Default and initial values to be returned
       */
      $result = array();
      $result['name'] = $field;
      $result['reference'] = isset($reference->$field) ? $reference->$field : null;
      $result['referenceCoeff'] = $result['reference']; //by default, no coeff applied because we may be comparing 2 strings
      $result['compared'] = $this->owner->$field;
      $result['trend'] = '';
      $result['trendCoeff'] = $result['trend'];         //by default, no coeff applied because we may be comparing 2 strings

      /**
       * Numeric comparison
       */
      if(is_numeric($result['compared']) && is_numeric($result['reference'])) {
        $result['compare'] = 'numeric';
        // We cast in float because a numeric could be a string (ex: money fields in gii)
        $result['reference'] = ((float)$result['reference']);
        $result['referenceCoeff'] = ((float)$result['reference']) * $coeff;
        $result['diffValue'] = (float)$result['compared'] - (float)$result['reference'];
        $result['diffValueCoeff'] = (float)$result['compared'] - (float)$result['referenceCoeff'];
        $result['compared'] = (float)$result['compared'];
        
        $result = $this->getDiffPercentNumeric($result);
        $result = $this->getDiffPercentNumeric($result, "Coeff");
      }
     
      /**
       * Boolean comparison
       */
      if(is_bool($result['compared']) && is_bool($result['reference'])) {
        $result['compare'] = 'bool';
        $result['diffValue'] = (int)$result['reference'] - (int)$result['compared'];
        $result['diffValueCoeff'] = $result['diffValue']; //There is no coeff to apply on bool.
        $result['diffPercent'] = null;
        $result['diffPercentCoeff'] = null;
      }

      /**
       * String comparison
       * "Numeric comparison" can compare 2 strings such has "10.00" vs "123", so we must check that it didn't already happened
       */
      if(is_string($result['compared']) && is_string($result['reference']) && empty($result['trend'])) { 
        $result['compare'] = 'string';
        $result['diffValue'] = null;
        $result['diffValueCoeff'] = $result['diffValue'];
        $result['diffPercent'] = null;
        $result['diffPercentCoeff'] = $result['diffPercent'];
        $result['trendCoeff'] = $result['trend'];
      }

      /**
       * Comparison to null
       */
      if(is_null($result['reference'])) {
        if(is_numeric($result['compared'])) 
          $result['compared'] = (float)$result['compared']; 
        $result['compare'] = '';
        $result['diffValue'] = null;
        $result['diffValueCoeff'] = null;
        $result['diffPercent'] = null;
        $result['diffPercentCoeff'] = null;
      }

      $result = $this->getTrend($result);
      $result = $this->getTrend($result, "Coeff");
      return (object)$result;
    }
  }

  /**
   * Calculate the percentage of difference
   *
   * @param      &array  $result  The result array we are working on, passed by reference
   * @param      string  $suffix  The suffix name: "Coeff" or "" ex: "diffPercent" vs "diffPercentCoeff"
   */
  private function getDiffPercentNumeric($result, $suffix="") {
    if    ($result['reference'.$suffix] != 0) $result['diffPercent'.$suffix] = $result['diffValue'.$suffix] / (float)$result['reference'.$suffix];
    elseif($result['diffValue'.$suffix] == 0) $result['diffPercent'.$suffix] = 0;                                       // they are both equal to 0
    else                                      $result['diffPercent'.$suffix] = ($result['diffValue'.$suffix]<0)?-1:1 ;  // ex: reference = 0 & diffValue = -123 or +123: (+/-)100% difference
    return $result;
  }

  /**
   * Guess the Trend and it's related CSS class
   *
   * @param      &array  $result  The result array we are working on, passed by reference
   * @param      string  $suffix  The suffix name: "Coeff" or "" ex: "diffPercent" vs "diffPercentCoeff"
   */
  private function getTrend($result, $suffix="") {
    if(!is_null($result['reference'])) {
      if    ($result['reference'.$suffix]<$result['compared']) $result['trend'.$suffix] = self::TREND_UP;
      elseif($result['reference'.$suffix]>$result['compared']) $result['trend'.$suffix] = self::TREND_DOWN;
      else                                                     $result['trend'.$suffix] = self::TREND_EQUAL;

      /**
       * Trend icon CSS class
       */
      switch($result['trend'.$suffix]) {
        case self::TREND_UP   : $result['trendIcon'.$suffix] = self::CLASS_UP   ; break;
        case self::TREND_DOWN : $result['trendIcon'.$suffix] = self::CLASS_DOWN ; break;
        case self::TREND_EQUAL: $result['trendIcon'.$suffix] = self::CLASS_EQUAL; break;
        default:                $result['trendIcon'.$suffix] = '';                break;
      }
    }
    return $result;
  }
}