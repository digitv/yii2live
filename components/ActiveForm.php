<?php

namespace digitv\yii2live\components;

use digitv\yii2live\components\form\ActiveField;
use yii\bootstrap\ActiveForm as bootstrapActiveForm;

/**
 * Class ActiveForm
 * @package digitv\yii2live\components
 */
class ActiveForm extends bootstrapActiveForm
{
    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * @see fieldConfig
     */
    public $fieldClass = 'digitv\yii2live\components\form\ActiveField';

    /**
     * @inheritdoc
     * @return ActiveField the created ActiveField object
     */
    public function field($model, $attribute, $options = [])
    {
        return parent::field($model, $attribute, $options);
    }
}