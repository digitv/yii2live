<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\behaviors\WidgetBehavior;
use digitv\yii2live\helpers\Html;
use digitv\yii2live\Yii2Live;
use Yii;
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
    /** @var bool Enable like old good Pjax */
    public $pjax = false;
    /** @var string live context (Yii2Live::CONTEXT_TYPE_PAGE | Yii2Live::CONTEXT_TYPE_PARTIAL | Yii2Live::CONTEXT_TYPE_PARENT | element ID) */
    public $liveContext;
    /** @var bool Enable pushState on AJAX request */
    public $livePushState;
    /** @var string Request method for live requests */
    public $liveRequestMethod;
    /** @var bool Enable replace animation on AJAX request */
    public $liveReplaceAnimation;

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

        //Clear view data on this widget context
        if($this->isLiveRequest()) {
            $live = Yii2Live::getSelf();
            if($live->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT && $live->getContextId() === $this->id) {
                Yii::$app->view->clear();
            }
        }

        ob_start();
        ob_implicit_flush(false);
        Html::addCssClass($this->options, 'yii2-live-widget');
        //Set live settings if Pjax enabled
        if($this->pjax) {
            if(!isset($this->liveContext)) { $this->liveContext = $this->id; }
            if(!isset($this->livePushState)) { $this->livePushState = true; }
        }
        //Set live pushState && live context enabled attributes
        $tag = Html::beginTag($this->tag, $this->options, true);
        if(isset($this->livePushState) || isset($this->liveContext)) {
            $tag->context($this->liveContext)
                ->pushState($this->livePushState);
            if(isset($this->liveRequestMethod)) { $tag->requestMethod($this->liveRequestMethod); }
        }
        //Set replace animation flag
        if(isset($this->liveReplaceAnimation)) {
            $tag->replaceAnimation($this->liveReplaceAnimation);
        }
        echo $tag;
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