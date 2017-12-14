<?php

namespace digitv\yii2live\components;

use digitv\yii2live\components\form\ActiveField;
use digitv\yii2live\Yii2Live;
use digitv\yii2live\yii2sockets\YiiNodeSocketFrameLoader;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\bootstrap\ActiveForm as bootstrapActiveForm;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package digitv\yii2live\components
 */
class ActiveForm extends bootstrapActiveForm
{
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoIdPrefix = 'form-';
    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * @see fieldConfig
     */
    public $fieldClass = 'digitv\yii2live\components\form\ActiveField';
    /** @var string Title for loading progress messages */
    public $title;

    /** @var array Live options call stack for HtmlChain object */
    protected $liveOptionsStack = [];

    protected $livePushStateOverwritten = false;

    public function init()
    {
        $this->on(static::EVENT_INIT, [$this, 'afterInit']);
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    /**
     * @inheritdoc
     * @return ActiveField the created ActiveField object
     */
    public function field($model, $attribute, $options = [])
    {
        /** @var ActiveField $field */
        $field = parent::field($model, $attribute, $options);
        return $field;
    }

    /**
     * Runs the widget.
     * This registers the necessary JavaScript code and renders the form open and close tags.
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching.
     */
    public function run()
    {
        $this->applyLiveOptionsStack();
        parent::run();
    }

    /**
     * After init
     * @param Event $event
     * @return bool
     */
    public function afterInit($event = null) {
        $component = Yii2Live::getSelf();
        if(!$component || !$component->enable) return true;
        //Node.js sockets
        $nodeJsTrigger = $component->getContextType() !== Yii2Live::CONTEXT_TYPE_EXACT
            || ($component->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $component->getContextId() === $this->id);
        if(isset($this->title) && $nodeJsTrigger) {
            $component->progressMessageAdd($this->title . '...', $this->id);
        }
        return true;
    }

    /**
     * This method is invoked right after a widget is executed.
     *
     * The method will trigger the [[EVENT_AFTER_RUN]] event. The return value of the method
     * will be used as the widget return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterRun($result)
     * {
     *     $result = parent::afterRun($result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param mixed $result the widget return result.
     * @return mixed the processed widget result.
     * @since 2.0.11
     */
    public function afterRun($result)
    {
        $component = Yii2Live::getSelf();
        if($component && $component->enable) {
            //Node.js sockets
            $nodeJsTrigger = $component->getContextType() !== Yii2Live::CONTEXT_TYPE_EXACT
                || ($component->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $component->getContextId() === $this->id);
            if(isset($this->title) && $nodeJsTrigger) {
                $component->progressMessageFinish($this->id);
            }
        }
        return parent::afterRun($result);
    }

    /**
     * Set live AJAX enable flag
     * @param bool $enable
     * @return static
     */
    public function ajax($enable = true) {
        return $this->addLiveOptionToStack(__FUNCTION__, [$enable]);
    }

    /**
     * Set request method
     * @param string $method
     * @return static
     */
    public function requestMethod($method = 'post') {
        $this->method = strtolower($method);
        if($this->method === 'get' && !$this->livePushStateOverwritten) $this->pushState(true);
        return $this;
    }

    /**
     * Set live context
     * @param string $contextValue
     * @return static
     */
    public function context($contextValue) {
        return $this->addLiveOptionToStack(__FUNCTION__, [$contextValue]);
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PARTIAL (shortcut)
     * @return static
     */
    public function contextPartial() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PARTIAL);
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PARENT (shortcut)
     * @return static
     */
    public function contextParent() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PARENT);
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PAGE (shortcut)
     * @return static
     */
    public function contextPage() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PAGE);
    }

    /**
     * Set pushState enable flag
     * @param bool $enabled
     * @return static
     */
    public function pushState($enabled = true) {
        $this->livePushStateOverwritten = true;
        return $this->addLiveOptionToStack(__FUNCTION__, [$enabled]);
    }

    /**
     * Apply all live options to element
     */
    protected function applyLiveOptionsStack() {
        $chain = new HtmlChain(['type' => HtmlChain::TYPE_BEGIN_TAG, 'tag' => 'form']);
        foreach ($this->liveOptionsStack as $row) {
            call_user_func_array([$chain, $row['method']], $row['arguments']);
        }
        //Simulate render
        $empty = (string)$chain;
        //merge options obtained from HtmlChain
        $this->options = ArrayHelper::merge($this->options, $chain->options);
    }

    /**
     * Add live option to stack
     * @param string $method
     * @param array  $arguments
     * @return $this
     */
    protected function addLiveOptionToStack($method, $arguments = []) {
        $this->liveOptionsStack[] = [
            'method' => $method,
            'arguments' => $arguments,
        ];
        return $this;
    }
}