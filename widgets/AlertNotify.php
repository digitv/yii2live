<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\Yii2Live;
use yii\bootstrap\Widget;
use yii\web\JsExpression;

/**
 * Class AlertNotify
 */
class AlertNotify extends Widget
{
    public static $counter = 0;
    public static $autoIdPrefix = 'alert-notify-';

    public $icon;
    public $title;
    public $message;
    public $url;
    public $target = '_blank';

    public $type = 'success';
    public $delay = 5000;
    public $showProgressbar = true;
    public $allowDismiss = true;
    public $template = '<div data-notify="container" class="yii2-live-notify col-xs-11 col-sm-3 alert alert-{0}" role="alert">' .
    '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' .
    '<span data-notify="icon"></span> ' .
    '<span data-notify="title">{1}</span> ' .
    '<span data-notify="message">{2}</span>' .
    '<div class="progress" data-notify="progressbar">' .
    '<div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' .
    '</div>' .
    '<a href="{3}" target="{4}" data-notify="url"></a>' .
    '</div>' ;

    protected static $types = ['success', 'danger', 'warning', 'info'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if(!isset($this->type) || !in_array($this->type, static::$types)) $this->type = reset(static::$types);
        if(isset($this->icon)) {
            $this->icon = strpos($this->icon, 'fa ') ? $this->icon : 'fa ' . $this->icon;
        } else {
            $this->setIconByType();
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $settings = $this->getPluginSettings();
        $options = $this->getPluginOptions();
        $live = Yii2Live::getSelf();
        if(isset($options)) {
            if($live && $live->enable && $live->isLiveRequest()) {
                $live->commands()->messageAdd($this->getMessageOrTitle(), $this->type);
            } else {
                $view = \Yii::$app->view;
                $optionsJs = json_encode($options, JSON_FORCE_OBJECT + JSON_UNESCAPED_UNICODE);
                $settingsJs = json_encode($settings, JSON_FORCE_OBJECT + JSON_UNESCAPED_UNICODE);
                $js = new JsExpression("$.notify({$optionsJs}, {$settingsJs});");
                $view->registerJs($js);
            }
        }
        parent::run();
    }

    /**
     * Get settings
     * @return array
     */
    protected function getPluginSettings() {
        return [
            'delay' => $this->delay,
            'type' => $this->type,
            'showProgressbar' => $this->showProgressbar,
            'allow_dismiss' => $this->allowDismiss,
            'template' => $this->template,
        ];
    }

    /**
     * Get options
     * @return array
     */
    protected function getPluginOptions() {
        if(!isset($this->message) && !isset($this->title)) return null;
        $keys = ['message', 'title', 'icon', 'url', 'target'];
        $options = [];
        foreach ($keys as $key) {
            if(!isset($this->{$key})) continue;
            $options[$key] = $this->{$key};
        }
        return $options;
    }

    /**
     * Set icon by alert type
     */
    protected function setIconByType() {
        $icons = [
            'success' => 'fa fa-check-circle',
            'info' => 'fa fa-info-circle',
            'danger' => 'fa fa-exclamation-triangle',
            'warning' => 'fa fa-exclamation-triangle',
        ];
        $this->icon = isset($icons[$this->type]) ? $icons[$this->type] : reset($icons);
    }

    /**
     * Get message or title (If whatever is set)
     * @return string|null
     */
    protected function getMessageOrTitle() {
        if(isset($this->message)) return $this->message;
        return isset($this->title) ? $this->title : null;
    }
}