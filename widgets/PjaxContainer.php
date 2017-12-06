<?php

namespace digitv\yii2live\widgets;

/**
 * Class PjaxContainer
 * An alias to HtmlInline widget (with property pjax set to `true`)
 */
class PjaxContainer extends HtmlInline
{
    public static $autoIdPrefix = 'html-pjax-cont-';

    public $pjax = true;
}