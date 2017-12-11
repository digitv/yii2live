<?php

namespace digitv\yii2live\components;

use digitv\yii2live\Yii2Live;
use yii\base\Object;
use yii\bootstrap\Html as bsHtml;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class HtmlChain
 */
class HtmlChain extends Object
{
    const TYPE_LINK         = 'a';
    const TYPE_TAG          = 'tag';
    const TYPE_BEGIN_TAG    = 'tagBegin';

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
        if(!isset($contextValue)) return $this;
        $this->liveOptions['context'] = $contextValue;
        if($contextValue !== Yii2Live::CONTEXT_TYPE_PAGE) {
            $this->pushState(false);
        }
        return $this;
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PARTIAL (shortcut)
     * @return HtmlChain
     */
    public function contextPartial() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PARTIAL);
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PARENT (shortcut)
     * @return HtmlChain
     */
    public function contextParent() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PARENT);
    }

    /**
     * Set live context to Yii2Live::CONTEXT_TYPE_PAGE (shortcut)
     * @return HtmlChain
     */
    public function contextPage() {
        return $this->context(Yii2Live::CONTEXT_TYPE_PAGE);
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
        $enabled = isset($enabled) ? !empty($enabled) : true;
        $this->liveOptions['pushState'] = $enabled;
        return $this;
    }

    /**
     * Set replaceAnimation enable flag
     * @param bool $enabled
     * @return $this
     */
    public function replaceAnimation($enabled = true) {
        $enabled = isset($enabled) ? !empty($enabled) : true;
        $this->liveOptions['replaceAnimation'] = $enabled;
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
            case static::TYPE_TAG:
            case static::TYPE_BEGIN_TAG:
                $liveOptions = $this->getLiveAttributes();
                if(strtolower($this->tag) === 'form') {
                    if(isset($liveOptions['data-live-method'])) {
                        $this->options['method'] = $liveOptions['data-live-method'];
                        unset($liveOptions['data-live-method']);
                    }
                }
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
        $rawAttributes = ['enabled', 'context', 'pushState', 'replaceAnimation', 'confirm', 'method'];
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
            case static::TYPE_TAG:
            case static::TYPE_LINK:
                return bsHtml::tag($this->tag, $this->tagContent, $this->options);
                break;
            case static::TYPE_BEGIN_TAG:
                return bsHtml::beginTag($this->tag, $this->options) . $this->tagContent;
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