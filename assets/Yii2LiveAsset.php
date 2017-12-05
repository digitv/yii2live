<?php

namespace digitv\yii2live\assets;

use digitv\yii2live\components\View;
use digitv\yii2live\Yii2Live;
use yii\web\AssetBundle;
use yii\web\JsExpression;

/**
 * Class Yii2AsyncAsset
 * main asset bundle
 */
class Yii2LiveAsset extends AssetBundle
{
    public $name = 'Yii2Live main asset bundle';
    public $sourcePath = '@yii2live/static';
    public $js = [
        'js/yii2live.cmd.js',
        'js/yii2live.base.js',
    ];
    public $css = [
        'css/yii2.live.css',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
        'digitv\yii2live\assets\Yii2LiveAssetFA',
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        $live = Yii2Live::getSelf();
        $settings = [
            'headerName' => $live->headerName,
            'headerNameContext' => $live->headerNameContext,
            'enableLiveLoad' => $live->enableLiveLoad,
            'enableReplaceAnimation' => $live->enableReplaceAnimation,
            'requestId' => $live->getRequestId(),
            'linkSelector' => $live->linkSelector,
            'linkSelectorAjax' => $live->linkSelectorAjax,
            'formSelector' => $live->formSelector,
            'formSelectorAjax' => $live->formSelectorAjax,
            'modalDefaultSelector' => $live->modalDefaultSelector,

            'messageAdapter' => $live->messageAdapter,

            'contexts' => [
                'page' => Yii2Live::CONTEXT_TYPE_PAGE,
                'partial' => Yii2Live::CONTEXT_TYPE_PARTIAL,
                'parent' => Yii2Live::CONTEXT_TYPE_PARENT,
            ],
        ];
        $settingsJson = json_encode($settings);
        $jsSettings = new JsExpression("
        if(typeof yii2live === 'undefined') yii2live = new Yii2Live(" . $settingsJson . ");
        else yii2live.settings.merge(" . $settingsJson . ");
        ");
        /** @var $view View|\yii\web\View */
        parent::registerAssetFiles($view);
        $view->registerJs((string)$jsSettings);
    }
}