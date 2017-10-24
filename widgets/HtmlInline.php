<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\behaviors\WidgetBehavior;
use digitv\yii2live\Yii2Live;
use yii\bootstrap\Html;
use yii\bootstrap\Widget;

/**
 * Class HtmlInline
 * Base live widget for HTML content
 * @package digitv\yii2live\widgets
 * @method boolean isLiveRequest
 */
class HtmlInline extends Widget
{
    public $tag = 'div';

    public $widgetResult;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => WidgetBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        //Bootstrap widget init
        parent::init();

        ob_start();
        ob_implicit_flush(false);
        Html::addCssClass($this->options, 'yii2-live-widget');
        echo Html::beginTag($this->tag, $this->options);
        if(!$this->isLiveRequest()) {}
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $content = ob_get_clean();
        $content .= Html::endTag($this->tag);
        if(!$this->isLiveRequest()) {}
        return $content;
    }
}