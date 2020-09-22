<?php

namespace digitv\yii2live\components\form;

use Yii;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;
use digitv\yii2live\Yii2Live;
use digitv\yii2live\helpers\Html;
use yii\bootstrap\ActiveField as bootstrapActiveField;

/**
 * Class ActiveField
 * @package digitv\yii2live\components\form
 */
class ActiveField extends bootstrapActiveField
{
    //Live options
    protected $liveOptions = [];

    /**
     * kartik Select2 widget
     *
     * @param  array $items
     * @param  array $options
     * @return $this
     */
    public function select2($items = [], $options = [])
    {
        $defaultOptions = [
            'data' => $items,
            'theme' => \kartik\widgets\Select2::THEME_BOOTSTRAP,
            'options' => [
                'placeholder' => Yii::t('back', 'Please select...'),
            ],
            'pluginOptions' => [
                'allowClear' => empty($options['options']['multiple']),
            ],
            'toggleAllSettings' => [
                'selectLabel' => '<i class="fa fa-check-square-o"></i> ' . Yii::t('back', 'Select all'),
                'unselectLabel' => '<i class="fa fa-times-rectangle-o"></i> ' . Yii::t('back', 'Remove selection'),
            ],
        ];
        //Check for formatted array data for future use
        if (! empty($items)) {
            $firstItem = reset($items);
            if (is_array($firstItem) && isset($firstItem['text'], $firstItem['id'])) {
                $simpleItems = ArrayHelper::map($items, 'id', 'text');
                $defaultOptions['data'] = $simpleItems;
                foreach ($items as $itemKey => $item) {
                    $items[$itemKey]['str'] = $item['text'];
                }
                $defaultOptions['pluginOptions']['data'] = $items;
                $defaultOptions['pluginOptions']['escapeMarkup'] = new JsExpression('function (markup) { return markup; }');
                $defaultOptions['pluginOptions']['templateResult'] = new JsExpression("function(row){ return row.str; }");
                $defaultOptions['pluginOptions']['templateSelection'] = new JsExpression("function(row){ return row.str; }");
                if (isset($firstItem['icon'])) {
                    $defaultOptions['pluginOptions']['templateResult'] = new JsExpression("function(row){ return $('<div class=\"select2-icon-row\"><span class=\"row-icon\">'+row.icon+'</span><span class=\"row-text\"> '+row.str+'</span></div>'); }");
                }
            }
        }
        $options = ArrayHelper::merge($defaultOptions, $options);
        Html::addCssClass($options['options'], 'compact-form-select2');

        return $this->widget(\kartik\widgets\Select2::class, $options);
    }

    /**
     * Dependent dropdown
     *
     * @param  array $options
     * @param  bool  $multiple
     * @param  bool  $typeSelect2
     * @return $this
     */
    public function dependentDropDown($options = [], $multiple = false, $typeSelect2 = false)
    {
        $defaultOptions = [
            'options' => [
                'placeholder' => Yii::t('back', 'Please select...'),
                'multiple' => ! empty($multiple),
            ],
            'pluginOptions' => [
                'placeholder' => Yii::t('back', 'Please select...'),
            ],
        ];
        if ($typeSelect2) {
            $defaultOptions['type'] = \kartik\widgets\DepDrop::TYPE_SELECT2;
            $defaultOptions['select2Options'] = [
                'language' => Yii::$app->language,
                'theme' => \kartik\widgets\Select2::THEME_BOOTSTRAP,
                'pluginOptions' => [
                    'allowClear' => false,
                ],
                'toggleAllSettings' => [
                    'selectLabel' => '<i class="fa fa-check-square-o"></i> ' . Yii::t('back', 'Select all'),
                    'unselectLabel' => '<i class="fa fa-times-rectangle-o"></i> ' . Yii::t('back', 'Remove selection'),
                ],
            ];
        }
        $options = ArrayHelper::merge($defaultOptions, $options);

        return $this->widget(\kartik\widgets\DepDrop::class, $options);
    }

    /**
     * kartik TypeAhead widget
     *
     * @param  array $options
     * @return $this
     */
    public function typeAhead($options = [])
    {
        return $this->widget(\kartik\widgets\Typeahead::class, $options);
    }


    /**
     * DatePicker krajee
     *
     * @param  array  $options
     * @param  string $format
     * @return $this
     */
    public function datePicker($options = [], $format = null)
    {
        $defaultOptions = [
            'convertFormat' => true,
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'php:Y-m-d',
            ],
        ];
        if (isset($format)) {
            $defaultOptions['pluginOptions']['format'] = $format;
        }
        $options = ArrayHelper::merge($defaultOptions, $options);

        return $this->widget(\kartik\widgets\DatePicker::class, $options);
    }

    /**
     * DateTimePicker krajee
     *
     * @param  array       $options
     * @param  string|null $format
     * @return $this
     */
    public function dateTimePicker($options = [], $format = null)
    {
        $defaultOptions = [
            'convertFormat' => true,
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'php:Y-m-d H:i',
            ],
        ];
        if (isset($format)) {
            $defaultOptions['pluginOptions']['format'] = $format;
        }
        $options = ArrayHelper::merge($defaultOptions, $options);

        return $this->widget(\kartik\widgets\DateTimePicker::class, $options);
    }

    /**
     * DateRangePicker krajee
     *
     * @param  array $options
     * @return $this
     */
    public function dateRangePicker($options = [])
    {
        $defaultOptions = [
            'presetDropdown' => true,
            'convertFormat' => true,
            'options' => [
                'placeholder' => 'none',
            ],
            'pluginOptions' => [
                'locale' => [
                    'format' => 'Y-m-d',
                    'separator' => ' - ',
                ],
            ],
            'hideInput' => true,
            'pluginEvents' => [
                'cancel.daterangepicker' => "function(ev, picker) {
                        $(picker.element).siblings('input').val('').change();
                        $(picker.element).find('.range-value').html('');
                        picker.updateView();
                    }",
            ],
        ];
        $options = ArrayHelper::merge($defaultOptions, $options);

        return $this->widget(\kartik\daterange\DateRangePicker::class, $options);
    }

    /**
     * Renders ToggleButtonGroup as checkboxes
     *
     * @param  array  $items
     * @param  string $btnClass
     * @param  array  $options
     * @return $this the field object itself
     */
    public function checkboxButtonGroup($items, $btnClass = 'btn-default', $options = [])
    {
        $options = ArrayHelper::merge($options, [
            'type' => 'checkbox',
            'items' => $items,
            'options' => [
                'itemOptions' => $this->getLiveAttributes(),
            ],
            'labelOptions' => [
                'class' => $btnClass,
            ],
        ]);
        $widgetClass = ArrayHelper::remove($options, 'class', \yii\bootstrap\ToggleButtonGroup::class);

        return $this->widget($widgetClass, $options);
    }

    /**
     * Renders ToggleButtonGroup as radios
     *
     * @param  array  $items
     * @param  string $btnClass
     * @param  array  $options
     * @return $this the field object itself
     */
    public function radioButtonGroup($items, $btnClass = 'btn-default', $options = [])
    {
        $options = ArrayHelper::merge($options, [
            'type' => 'radio',
            'items' => $items,
            'options' => [
                'itemOptions' => $this->getLiveAttributes(),
            ],
            'labelOptions' => [
                'class' => $btnClass,
            ],
        ]);
        $options['emptyOptionAsNone'] = true;
        $widgetClass = ArrayHelper::remove($options, 'class', \yii\bootstrap\ToggleButtonGroup::class);

        return $this->widget($widgetClass, $options);
    }

    /**
     * @inheritdoc
     */
    public function input($type, $options = [])
    {
        $this->getLiveAttributes(true);

        return parent::input($type, $options);
    }

    /**
     * @inheritdoc
     */
    public function textInput($options = [])
    {
        $this->getLiveAttributes(true);

        return parent::textInput($options);
    }

    /**
     * @inheritdoc
     */
    public function textarea($options = [])
    {
        $this->getLiveAttributes(true);

        return parent::textarea($options);
    }

    /**
     * @inheritdoc
     */
    public function radio($options = [], $enclosedByLabel = true)
    {
        $liveAttributes = $this->getLiveAttributes(false);
        if (! empty($liveAttributes)) {
            $options = ArrayHelper::merge($options, $liveAttributes);
        }

        return parent::radio($options, $enclosedByLabel);
    }

    /**
     * @inheritdoc
     */
    public function checkbox($options = [], $enclosedByLabel = true)
    {
        $liveAttributes = $this->getLiveAttributes(false);
        if (! empty($liveAttributes)) {
            $options = ArrayHelper::merge($options, $liveAttributes);
        }

        return parent::checkbox($options, $enclosedByLabel);
    }

    /**
     * @inheritdoc
     */
    public function dropDownList($items, $options = [])
    {
        $this->getLiveAttributes(true);

        return parent::dropDownList($items, $options);
    }

    /**
     * @inheritdoc
     */
    public function checkboxList($items, $options = [])
    {
        $this->getLiveAttributes(true);

        return parent::checkboxList($items, $options);
    }

    /**
     * @inheritdoc
     */
    public function radioList($items, $options = [])
    {
        $this->getLiveAttributes(true);

        return parent::radioList($items, $options);
    }

    /**
     * Renders a widget as the input of the field.
     *
     * Note that the widget must have both `model` and `attribute` properties. They will
     * be initialized with [[model]] and [[attribute]] of this field, respectively.
     *
     * If you want to use a widget that does not have `model` and `attribute` properties,
     * please use [[render()]] instead.
     *
     * For example to use the [[MaskedInput]] widget to get some date input, you can use
     * the following code, assuming that `$form` is your [[ActiveForm]] instance:
     *
     * ```php
     * $form->field($model, 'date')->widget(\yii\widgets\MaskedInput::class, [
     *     'mask' => '99/99/9999',
     * ]);
     * ```
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @param  string $class  the widget class name.
     * @param  array  $config name-value pairs that will be used to initialize the widget.
     * @return $this the field object itself.
     */
    public function widget($class, $config = [])
    {
        return parent::widget($class, $config);
    }

    /**
     * @inheritdoc
     */
    public function render($content = null)
    {
        return parent::render($content);
    }


    /**
     * Set live AJAX enable flag
     *
     * @param  bool|string|array $enable
     * @return ActiveField
     */
    public function ajax($enable = true)
    {
        $url = null;
        if (is_array($enable)) {
            $url = Url::to($enable);
        }
        if ($url !== null) {
            $enable = true;
            $this->liveOptions['url'] = $url;
        }
        $this->liveOptions['enabled'] = $enable;

        return $this;
    }

    /**
     * Set live context
     *
     * @param $contextValue
     * @return ActiveField
     */
    public function context($contextValue)
    {
        if (! isset($contextValue)) {
            return $this;
        }
        $this->liveOptions['context'] = $contextValue;
        if ($contextValue !== Yii2Live::CONTEXT_TYPE_PAGE) {
            $this->pushState(false);
        }

        return $this;
    }

    /**
     * Set request method
     *
     * @param  string $method
     * @return ActiveField
     */
    public function requestMethod($method = 'get')
    {
        $this->liveOptions['method'] = strtolower($method);

        return $this;
    }

    /**
     * Set pushState enable flag
     *
     * @param  bool $enabled
     * @return ActiveField
     */
    public function pushState($enabled = true)
    {
        $enabled = isset($enabled) ? ! empty($enabled) : true;
        $this->liveOptions['pushState'] = $enabled;

        return $this;
    }

    /**
     * Get liveOptions as attributes array
     *
     * @param  bool $updateInputOptions
     * @return array
     */
    protected function getLiveAttributes($updateInputOptions = false)
    {
        $attributes = [];
        $rawAttributes = ['enabled', 'context', 'pushState', 'method', 'url'];
        //Write raw attributes values
        foreach ($rawAttributes as $key) {
            if (! isset($this->liveOptions[$key])) {
                continue;
            }
            $attributeName = 'data-live-' . Inflector::camel2id($key);
            $attributeValue = is_bool($this->liveOptions[$key]) ? (int)$this->liveOptions[$key] : $this->liveOptions[$key];
            $attributes[$attributeName] = $attributeValue;
        }
        //Disable Pjax
        if (! empty($this->liveOptions['enabled']) || isset($this->liveOptions['context'])) {
            $attributes['data-pjax'] = 0;
        }
        //Copy context from form
        if (! isset($this->liveOptions['context']) && ! empty($this->liveOptions['enabled']) && ! empty($this->form->options['data-live-context'])) {
            $attributes['data-live-context'] = $this->form->options['data-live-context'];
        }
        if ($updateInputOptions) {
            $this->inputOptions = ArrayHelper::merge($this->inputOptions, $attributes);
        }

        return $attributes;
    }
}