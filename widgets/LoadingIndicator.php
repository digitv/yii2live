<?php

namespace digitv\yii2live\widgets;

use Yii;
use yii\bootstrap\Html;
use yii\bootstrap\Widget;

/**
 * Class LoadingIndicator
 *
 */
class LoadingIndicator extends Widget
{
    public $faIcon = 'fa-refresh';

    public $options = [];

    public static $autoIdPrefix = 'lw-loader';

    /**
     * Get loading animation spinner
     * @return string
     */
    protected function getLoader() {
        $iconOptions = ['class' => 'fa'];
        $tagOptions = [
            'class' => 'loading-animation',
        ];
        $classes = [$this->faIcon, 'fa-spin'];
        Html::addCssClass($iconOptions, $classes);
        $icon = Html::tag('i', '', $iconOptions);
        return Html::tag('div', $icon, $tagOptions);
    }

    protected function getLoadingText() {
        $text = Yii::t('back', 'Loading...');
        return Html::tag('div', $text, ['class' => 'loading-text']);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $content = [
            $this->getLoadingText(),
            $this->getLoader(),
        ];
        Html::addCssClass($this->options, ['yii2-live-loading-indicator']);
        return Html::tag('div', implode("\n", $content), $this->options);
    }
}