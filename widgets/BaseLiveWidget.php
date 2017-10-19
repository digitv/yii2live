<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\Yii2Live;
use yii\bootstrap\Widget;

/**
 * Class BaseLiveWidget
 */
class BaseLiveWidget extends Widget
{
    public static $autoIdPrefix = 'lw';

    public $widgetResult = [];
}