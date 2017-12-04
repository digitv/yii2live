<?php

namespace digitv\yii2live\widgets;

use digitv\yii2live\behaviors\WidgetBehavior;
use digitv\yii2live\Yii2Live;
use yii\bootstrap\BootstrapAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class Nav
 *
 * @property string $widgetType
 * @method bool|array checkWidgetState(array $data, bool $saveState, bool $checkLanguage)
 */
class Nav extends \yii\bootstrap\Nav
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => WidgetBehavior::className(),
                'widgetType' => WidgetBehavior::LIVE_WIDGET_TYPE_COMMANDS,
            ],
        ];
    }

    /**
     * Renders the widget.
     */
    public function run()
    {
        BootstrapAsset::register($this->getView());
        $live = Yii2Live::getSelf();
        if($live) {
            $stateCheck = $this->computeLiveState();
            if($live->isLiveRequest()) {
                if($stateCheck === true) return null;
                $this->widgetType = WidgetBehavior::LIVE_WIDGET_TYPE_HTML;
            }
        }
        return $this->renderItems();
    }

    /**
     * Compute widget state (changed or not)
     * @return bool
     */
    protected function computeLiveState() {
        $activeUrls = $this->getActiveUrls($this->items);
        $items = [];
        foreach ($this->items as $item) {
            if(is_string($item)) $items[] = $item;
            elseif(isset($item['label']) && isset($item['url'])) {
                $items[] = $item['label'] . ":" .  Url::to($item['url']);
            }
        }
        $data = [
            'activeUrls' => $activeUrls,
            'items' => md5(implode("\n", $items)),
        ];
        $live = Yii2Live::getSelf();
        $stateCheck = $this->checkWidgetState($data, true, false);
        if(is_bool($stateCheck)) return $stateCheck;
        if(is_array($stateCheck)) {
            if(in_array('items', $stateCheck)) return false;
            if(in_array('activeUrls', $stateCheck)) {
                $menuSelector = '#' . $this->id;
                $cmd = $live->commands();
                $cmd->jRemoveClass($menuSelector . ' li', 'active');
                foreach ($activeUrls as $url) {
                    $cmd->chainBegin()
                        ->jParent($menuSelector . ' a[href="'.$url.'"]')
                        ->jAddClass(null, 'active')
                        ->chainEnd();
                }
            }
            return true;
        }
        return true;
    }

    /**
     * Get active items URLs
     * @param array $items
     * @return array
     */
    public function getActiveUrls($items = []) {
        $urls = [];
        foreach ($items as $item) {
            if(!is_array($item)) continue;
            $active = $this->isItemActiveFully($item);
            if($active) $urls[] = Url::to($item['url']);
            if(!empty($item['items'])) {
                $urls = ArrayHelper::merge($urls, $this->getActiveUrls($item['items']));
            }
        }
        return $urls;
    }

    /**
     * Check that item is fully active
     * @param $item
     * @return bool
     */
    protected function isItemActiveFully($item)
    {
        if(isset($item['active']) && !empty($item['active'])) return true;
        return parent::isItemActive($item);
    }
}