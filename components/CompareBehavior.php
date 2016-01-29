<?php

namespace app\components;

use yii\base\Behavior;

class CompareBehavior extends Behavior
{
  /* Compare Trends */
  const TREND_UP    = 'up';
  const TREND_DOWN  = 'down';
  const TREND_EQUAL = 'equal';

  const CLASS_UP    = 'glyphicon glyphicon-chevron-up trend-icon trend-up';
  const CLASS_DOWN  = 'glyphicon glyphicon-chevron-down trend-icon trend-down';
  const CLASS_EQUAL = 'glyphicon glyphicon-minus trend-icon trend-equal';

  public function compare($reference, $fields = null, $coeff = 1) {
    $differences = array();

   if(is_null($fields) === true) {
  		foreach($this->owner->attributes as $key => $refValue)
  			$differences[$key] = $this->compareField($reference, $key, $coeff);
  	}
  	elseif(is_array($fields)) {
  		foreach($fields as $fieldName)
  			$differences[$fieldName] = $this->compareField($reference, $fieldName, $coeff);
  	}
    return $differences;
  }
 
	public function compareField($reference, $field, $coeff) {
		if(!isset($this->owner->$field)) { return null; }
		else {
			$property = array();
			$property['name'] = $field;
			$property['reference'] = isset($reference->$field) ? $reference->$field : null; //if not exist OR null, we put null
			$property['referenceCoeff'] = $property['reference'];
			$property['compared'] = $this->owner->$field;
	    $property['trend'] = '';
	    $property['trendCoeff'] = $property['trend'];

			/*Compare numerics*/
			if(is_numeric($property['compared']) && is_numeric($property['reference'])) {
		    $property['compare'] = 'numeric';
		    $property['reference'] = ((float)$property['reference']); //a numeric can be a string (money in gii)
		    $property['referenceCoeff'] = ((float)$property['reference']) * $coeff;
		    $property['diffValue'] = (float)$property['compared'] - (float)$property['reference'];
		    $property['diffValueCoeff'] = (float)$property['compared'] - (float)$property['referenceCoeff'];
		    $property['compared'] = (float)$property['compared'];
			    
				/*calculation diffPercent*/
				if    ($property['reference'] != 0) $property['diffPercent'] = $property['diffValue'] / (float)$property['reference']; // can not divide by 0
				elseif($property['diffValue'] == 0) $property['diffPercent'] = 0; //both equal to 0
				else                                $property['diffPercent'] = ($property['diffValue']<0)?-1:1 ;//reference = 0 & diffValue = -123 or +123
				
				if    ($property['referenceCoeff'] != 0) $property['diffPercentCoeff'] = $property['diffValueCoeff'] / (float)$property['referenceCoeff']; // can not divide by 0
				elseif($property['diffValueCoeff'] == 0) $property['diffPercentCoeff'] = 0; //both equal to 0
				else                                     $property['diffPercentCoeff'] = ($property['diffValueCoeff']<0)?-1:1 ;//reference = 0 & diffValue = -123 or +123
			}
     
			/*Compare booleans*/
			if(is_bool($property['compared']) && is_bool($property['reference'])) {
        $property['compare'] = 'bool';
				$property['diffValue'] = (int)$property['reference'] - (int)$property['compared'];
				$property['diffValueCoeff'] = $property['diffValue']; //for bool, there is no coeff to apply on bool. It must be 1 or 0
				$property['diffPercent'] = null;
				$property['diffPercentCoeff'] = $property['diffPercent'];
			}
			
			if (isset($property['compare'])) {
        if    ($property['diffValue']>0) $property['trend'] = self::TREND_UP;
        elseif($property['diffValue']<0) $property['trend'] = self::TREND_DOWN;
        else                             $property['trend'] = self::TREND_EQUAL;

        if    ($property['diffValueCoeff']>0) $property['trendCoeff'] = self::TREND_UP;
        elseif($property['diffValueCoeff']<0) $property['trendCoeff'] = self::TREND_DOWN;
        else                                  $property['trendCoeff'] = self::TREND_EQUAL;
			}
     
			/*Compare strings, remember that "Compare numerics" can compare 2 strings such has "10.00" vs "123": so we must check that it didn't already happened*/
			if(is_string($property['compared']) && is_string($property['reference']) && empty($property['trend'])) { 
				$property['compare'] = 'string';
				$property['diffValue'] = null;
				$property['diffValueCoeff'] = $property['diffValue'];
				$property['diffPercent'] = null;
				$property['diffPercentCoeff'] = $property['diffPercent'];
				$property['trend'] = self::TREND_EQUAL;
				$property['trendCoeff'] = $property['trend'];
				if    ($property['reference']<$property['compared']) $property['trend'] = self::TREND_UP;
				elseif($property['reference']>$property['compared']) $property['trend'] = self::TREND_DOWN;
				else                                                 $property['trend'] = self::TREND_EQUAL;
				
				if    ($property['referenceCoeff']<$property['compared']) $property['trendCoeff'] = self::TREND_UP;
				elseif($property['referenceCoeff']>$property['compared']) $property['trendCoeff'] = self::TREND_DOWN;
				else                                                      $property['trendCoeff'] = self::TREND_EQUAL;
			}
			
			if($property['reference'] == null) {
			    if(is_numeric($property['compared'])) $property['compared'] = (float)$property['compared']; 
			    $property['compare'] = '';
			    $property['diffValue'] = null;
			    $property['diffValueCoeff'] = $property['diffValue'];
			    $property['diffPercent'] = null;
			    $property['diffPercentCoeff'] = $property['diffPercent'];
			}

			/*Assign CSS class Trend*/
			switch($property['trend']) {
			    case self::TREND_UP   : $property['trendIcon'] = self::CLASS_UP   ; break;
			    case self::TREND_DOWN : $property['trendIcon'] = self::CLASS_DOWN ; break;
			    case self::TREND_EQUAL: $property['trendIcon'] = self::CLASS_EQUAL; break;
			    default:                $property['trendIcon'] = '';                break;
			}
			
			switch($property['trendCoeff']) {
			    case self::TREND_UP   : $property['trendIconCoeff'] = self::CLASS_UP   ; break;
			    case self::TREND_DOWN : $property['trendIconCoeff'] = self::CLASS_DOWN ; break;
			    case self::TREND_EQUAL: $property['trendIconCoeff'] = self::CLASS_EQUAL; break;
			    default:                $property['trendIconCoeff'] = '';                break;
			}
            
			return (object)$property;
		}
	}
}