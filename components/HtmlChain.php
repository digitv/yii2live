<?php

namespace digitv\yii2live\components;

use digitv\yii2live\Yii2Live;
use yii\base\Object;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class HtmlChain
 */
class HtmlChain extends Object
{
    const TYPE_LINK = 'a';

    public $type = self::TYPE_LINK;

    public $tag = 'div';
    public $tagContent = '';

    //General properties
    public $options = [];
    //Live options
    protected $liveOptions = [];

    /**
     * Load data
     * @param array $config
     */
    public function load($config = []) {
        \Yii::configure($this, $config);
    }

    /**
     * Set live AJAX enable flag
     * @param bool $enable
     * @return HtmlChain
     */
    public function ajax($enable = true) {
        $this->liveOptions['enabled'] = $enable;
        return $this;
    }

    /**
     * Set live context
     * @param $contextValue
     * @return HtmlChain
     */
    public function context($contextValue) {
        $this->liveOptions['context'] = $contextValue;
        if($contextValue !== Yii2Live::CONTEXT_TYPE_PAGE) {
            $this->pushState(false);
        }
        return $this;
    }

    /**
     * Set confirm message
     * @param string $message
     * @return HtmlChain
     */
    public function confirm($message = null) {
        $this->liveOptions['confirm'] = $message;
        return $this;
    }

    /**
     * Set request method
     * @param string $method
     * @return HtmlChain
     */
    public function requestMethod($method = 'get') {
        $this->liveOptions['method'] = strtolower($method);
        return $this;
    }

    /**
     * Set pushState enable flag
     * @param bool $enabled
     * @return HtmlChain
     */
    public function pushState($enabled = true) {
        $this->liveOptions['pushState'] = $enabled;
        return $this;
    }

    /**
     * Process live options
     */
    protected function processLiveOptions() {
        switch ($this->type) {
            case static::TYPE_LINK:
                $liveOptions = $this->getLiveAttributes();
                $this->options = ArrayHelper::merge($this->options, $liveOptions);
                break;
        }
    }

    /**
     * Get liveOptions as attributes array
     * @return array
     */
    protected function getLiveAttributes() {
        $attributes = [];
        $rawAttributes = ['enabled', 'context', 'pushState', 'confirm', 'method'];
        //Write raw attributes values
        foreach ($rawAttributes as $key) {
            if(!isset($this->liveOptions[$key])) continue;
            $attributeName = 'data-live-' . Inflector::camel2id($key);
            $attributeValue = is_bool($this->liveOptions[$key]) ? (int)$this->liveOptions[$key] : $this->liveOptions[$key];
            $this->options[$attributeName] = $attributeValue;
        }
        //Disable Pjax
        if(!empty($this->liveOptions['enabled']) || isset($this->liveOptions['context'])) {
            $this->options['data-pjax'] = 0;
        }

        return $attributes;
    }

    /**
     * Render element
     * @return string
     */
    protected function render() {
        $this->processLiveOptions();
        switch ($this->type) {
            case static::TYPE_LINK:
                return Html::tag($this->tag, $this->tagContent, $this->options);
                break;
        }
        return '';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}