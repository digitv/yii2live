<?php

namespace digitv\yii2live\assets;

use yii\web\AssetBundle;

/**
 * Class Yii2LiveAssetNotify
 * main asset bundle
 */
class Yii2LiveAssetNotify extends AssetBundle
{
    public $name = 'Yii2Live main asset bundle (bootstrap notify)';
    public $sourcePath = '@bower/remarkable-bootstrap-notify/dist';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [];
    public $depends = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->js = [
            YII_DEBUG ? 'bootstrap-notify.js' : 'bootstrap-notify.min.js'
        ];
        parent::init();
    }
}