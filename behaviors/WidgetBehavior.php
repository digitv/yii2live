<?php

namespace digitv\yii2live\behaviors;

use digitv\yii2live\Yii2Live;
use digitv\yii2live\yii2sockets\YiiNodeSocketFrameLoader;
use Yii;
use yii\base\Behavior;
use yii\base\Widget;
use yii\base\WidgetEvent;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/**
 * Class WidgetBehavior
 *
 * @property Widget $owner
 * @property string $id
 */
class WidgetBehavior extends Behavior
{
    const LIVE_WIDGET_TYPE_HTML             = 'html';
    const LIVE_WIDGET_TYPE_CONFIGURABLE     = 'configurable';
    const LIVE_WIDGET_TYPE_COMBINED         = 'combined';
    const LIVE_WIDGET_TYPE_COMMANDS         = 'commands';

    public $widgetType                      = self::LIVE_WIDGET_TYPE_HTML;
    /** @var string jQuery insert method (insert|replace) */
    public $widgetInsertMethod              = 'replace';
    /** @var string Widget content or other string data */
    public $widgetDataHtml;
    /** @var array Widget data (array or object for JS configure) */
    public $widgetData;
    /** @var array Widget data (array or object for JS configure) */
    public $widgetParents = [];
    /** @var string JS configure callback */
    public $configureCallback;

    public $widgetStackParent;

    public static $widgetStackActive;

    public static $widgetStack = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Widget::EVENT_BEFORE_RUN => 'beforeRun',
            Widget::EVENT_AFTER_RUN => 'afterRun',
            Widget::EVENT_INIT => 'afterInit',
        ];
    }

    /**
     * Widget::EVENT_BEFORE_RUN event handler
     * @param $event
     */
    public function beforeRun($event) {}

    /**
     * Widget::EVENT_AFTER_RUN event handler
     * @param WidgetEvent $event
     */
    public function afterRun($event) {
        $component = Yii2Live::getSelf();
        //Node.js sockets
        $nodeJsTrigger = $component->getContextType() !== Yii2Live::CONTEXT_TYPE_EXACT
            || ($component->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $component->getContextId() === $this->id);
        if(isset($this->owner->title) && $nodeJsTrigger) {
            $component->progressMessageFinish($this->id);
        }
        if(isset($this->owner->widgetResult) && !empty($this->owner->widgetResult)) {
            $this->setLiveWidgetData($this->owner->widgetResult);
        } else {
            if(empty(trim($event->result))) {
                $event->result = Html::tag('div', '', ['id' => $this->id, 'class' => 'yii2-live-widget-empty']);
            }
            $this->setLiveWidgetData($event->result);
        }

        $this->postProcessWidgetStack();

        if($this->isLiveRequest()) {
            $this->dumpToResponse();
        }
    }

    /**
     * Widget::EVENT_INIT event handler
     * @param $event
     */
    public function afterInit($event) {
        $component = Yii2Live::getSelf();
        //Node.js sockets
        $nodeJsTrigger = $component->getContextType() !== Yii2Live::CONTEXT_TYPE_EXACT
            || ($component->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $component->getContextId() === $this->id);
        if(isset($this->owner->title) && $nodeJsTrigger) {
            $component->progressMessageAdd($this->owner->title . '...', $this->id);
        }
        $this->preProcessWidgetStack();
    }

    /**
     * Dump widget data to Response
     */
    public function dumpToResponse() {
        $component = Yii2Live::getSelf();
        $component->setWidgetData($this);
    }

    /**
     * Get widget data for Response
     * @return array
     */
    public function getWidgetLiveData() {
        $data = [
            'callback' => $this->getJsConfigureCallback(),
            'dataHtml' => $this->widgetDataHtml,
            'data' => $this->widgetData,
            'type' => $this->widgetType,
            'id' => $this->id,
            'insertMethod' => $this->widgetInsertMethod,
        ];
        if(isset($this->widgetStackParent)) {
            $data['parents'] = self::$widgetStack;
        }

        return $data;
    }

    /**
     * Set widget data
     * @param string|array|object $data
     */
    public function setLiveWidgetData($data) {
        switch ($this->widgetType) {
            case static::LIVE_WIDGET_TYPE_HTML:
                $this->widgetDataHtml = $data;
                break;
            case static::LIVE_WIDGET_TYPE_COMMANDS:
            case static::LIVE_WIDGET_TYPE_CONFIGURABLE:
                $this->widgetData = $data;
                break;
            case static::LIVE_WIDGET_TYPE_COMBINED:
                if(is_string($data)) $this->widgetDataHtml = $data;
                else $this->widgetData = $data;
                break;
        }
    }

    /**
     * Check if request is made with Yii2Live component
     * @return bool
     */
    public function isLiveRequest() {
        $component = Yii2Live::getSelf();
        return $component && $component->isLiveRequest();
    }

    /**
     * Check that context is this widget
     * @return bool
     */
    public function isThisContext() {
        $component = Yii2Live::getSelf();
        return $component->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $component->getContextId() === $this->id;
    }

    /**
     * Get widget ID
     * @return string
     */
    public function getId() {
        return $this->owner->id;
    }

    /**
     * Get JS configure callback
     * @return null|string
     */
    public function getJsConfigureCallback() {
        if(isset($this->configureCallback)) return $this->configureCallback;
        $types = static::getJsConfigureCallbacksByType();
        return isset($types[$this->widgetType]) ? $types[$this->widgetType] : null;
    }

    /**
     * Widget stack pre processing
     */
    public function preProcessWidgetStack() {
        static::$widgetStack[] = $this->id;
        if(isset(static::$widgetStackActive)) $this->widgetStackParent = static::$widgetStackActive;
        static::$widgetStackActive = $this->id;
    }

    /**
     * Widget stack post processing
     */
    public function postProcessWidgetStack() {
        $widgetId = array_pop(static::$widgetStack);
        if($widgetId === $this->id) {}
        static::$widgetStackActive = isset($this->widgetStackParent) ? $this->widgetStackParent : null;
    }

    /**
     * Check that widget state changed
     * @param array $stateData
     * @param bool  $saveState
     * @param bool  $checkLanguage
     * @return bool|array
     */
    public function checkWidgetState($stateData = [], $saveState = true, $checkLanguage = false) {
        $this->processWidgetState($stateData);
        $oldStateData = Yii2Live::getSelf()->getWidgetRequestState($this->id);
        $diff = $this->getWidgetStatesDifference($stateData, $oldStateData);
        if($saveState) {
            $this->setWidgetState($stateData);
        }
        //No difference
        if(empty($diff)) return true;
        //Difference in general key
        if(in_array('general', $diff)) return false;
        //Language mismatch
        if($checkLanguage && (!isset($oldStateData['lang']) || ($stateData['lang'] !== $oldStateData['lang']))) {
            return false;
        }
        return $diff;
    }

    /**
     * Get old and new state difference (array of keys)
     * @param array $stateData
     * @param array $oldStateData
     * @return array
     */
    public function getWidgetStatesDifference($stateData = [], $oldStateData = []) {
        if(!isset($oldStateData) || !is_array($oldStateData)) return array_keys($stateData);
        $diff = array_diff_key($oldStateData, $stateData);
        foreach ($stateData as $key => $value) {
            if(isset($oldStateData[$key]) && (string)$value === (string)$oldStateData[$key]) continue;
            $diff[] = $key;
        }
        return $diff;
    }

    /**
     * Set new widget state
     * @param array $data
     */
    public function setWidgetState($data = []) {
        $this->processWidgetState($data);
        Yii2Live::getSelf()->setWidgetRequestState($this->id, $data);
    }

    /**
     * Process widget state data
     * @param array $data
     * @param bool  $forceOneLevel
     * @return array
     */
    protected function processWidgetState(&$data = [], $forceOneLevel = true) {
        if(!is_array($data) || !ArrayHelper::isAssociative($data)) $data = ['general' => $data];
        if($forceOneLevel) {
            foreach($data as $key => $row) {
                if(is_scalar($row)) continue;
                $data[$key] = md5(json_encode($row, JSON_FORCE_OBJECT + JSON_UNESCAPED_UNICODE));
            }
        }
        $data['lang'] = Yii::$app->language;
        return $data;
    }

    /**
     * Get JS process callbacks array indexed by type
     * @return string[]
     */
    public static function getJsConfigureCallbacksByType() {
        return [
            static::LIVE_WIDGET_TYPE_HTML           => 'processHtml',
            static::LIVE_WIDGET_TYPE_CONFIGURABLE   => 'processConfigurable',
            static::LIVE_WIDGET_TYPE_COMBINED       => 'processCombined',
        ];
    }

}