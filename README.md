# Yii2 Compare behavior
This Yii2 behavior, can help you to compare all fields between 2 objects from the same class.

### Installation
Attach the behavior to your model class:
```
    use app\components\CompareBehavior;
    //[...]
    public function behaviors()
    {
      return [
          'compare' => [
              'class' => CompareBehavior::className(),
          ]
      ] ;
    }
```
### How to use ?
You can use the compare behavior in your controller:
  `$compare = $objectCurrent->compare($objectReference);`
  
And this behavior will returns for each common properties:
* name: The property name getting compared
* reference: The reference value
* compared: The current object value
* diffValue: The difference between compared and reference
* diffPercent: diffValue / reference
* compare: What comparison type. It can be numeric, bool or string
* trend: The trend between reference & compared. It can be up, down or equal
* trendIcon: glyphicon CSS class matching the trend value
* referenceCoeff: The reference value x the (optional) coeff
* diffValueCoeff: The difference between compared and referenceCoeff
* diffPercentCoeff: diffValueCoeff / referenceCoeff
* trendCoeff: The trend between referenceCoeff & compared. It can be up, down or equal
* trendIconCoeff: glyphicon CSS class matching the trendCoeff value

