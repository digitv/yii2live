<?php

namespace digitv\yii2live\assets;

use yii\web\AssetBundle;

/**
 * Class Yii2LiveAssetFA (Font Awesome)
 */
class Yii2LiveAssetFA extends AssetBundle {
    public $name = 'Yii2Live main asset bundle (Fort Awesome (FA))';
    public $sourcePath = '@bower/font-awesome';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [];
    public $depends = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->css = [
            YII_DEBUG ? 'css/font-awesome.css' : 'css/font-awesome.min.css'
        ];
        parent::init();
    }
}