<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\Yii2Live;
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

    /**
     * Get `loading...` text
     * @return string
     */
    protected function getLoadingText() {
        $text = Yii::t('back', 'Loading...');
        return Html::tag('div', $text, ['class' => 'loading-text']);
    }

    /**
     * Get loading overlay html
     * @return string
     */
    protected function getLoadingOverlay() {
        $live = Yii2Live::getSelf();
        if(!$live->enableLoadingOverlay) return '';
        $iconOptions = ['class' => 'fa ' . $this->faIcon . ' fa-spin'];
        $icon = Html::tag('i', '', $iconOptions);
        return Html::tag('div', $icon, [
            'class' => 'loading-overlay',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        Html::addCssClass($this->options, ['yii2-live-loading-indicator']);
        $messageContent = [
            $this->getLoadingText(),
            $this->getLoader(),
        ];
        $messageArea = Html::tag('div', implode("\n", $messageContent), ['class' => 'message-area']);
        $content = [
            $messageArea,
            $this->getLoadingOverlay(),
        ];
        return Html::tag('div', implode("\n", $content), $this->options);
    }
}