<?php

namespace digitv\yii2live\assets;

use yii\web\AssetBundle;

/**
 * Class Yii2LiveAssetAnimateCss
 * main asset bundle
 */
class Yii2LiveAssetAnimateCss extends AssetBundle
{
    public $name = 'Yii2Live main asset bundle (animate.css)';
    public $sourcePath = '@bower/animate.css';
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
            YII_DEBUG ? 'animate.css' : 'animate.min.css',
        ];
        parent::init();
    }
}