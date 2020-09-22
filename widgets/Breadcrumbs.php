<?php

namespace digitv\yii2live\widgets;

use Yii;
use digitv\yii2live\Yii2Live;
use digitv\yii2live\behaviors\WidgetBehavior;

/**
 * Class Breadcrumbs
 *
 * @property string $widgetType
 * @method bool|array checkWidgetState(array $data, bool $saveState, bool $checkLanguage)
 */
class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    /** @var string Title for loading progress messages */
    public $title;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => WidgetBehavior::class,
                'widgetType' => WidgetBehavior::LIVE_WIDGET_TYPE_COMMANDS,
            ],
        ];
    }

    public function init()
    {
        if (! isset($this->title)) {
            $this->title = Yii::t('yii2live', 'Breadcrumbs');
        }
        parent::init();
        $this->id = $this->options['id'];
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $live = Yii2Live::getSelf();
        if ($live) {
            $stateCheck = $this->computeLiveState();
            if ($live->isLiveRequest()) {
                if ($stateCheck === true) {
                    return null;
                }
                $this->widgetType = WidgetBehavior::LIVE_WIDGET_TYPE_HTML;
            }
        }
        ob_start();
        ob_implicit_flush(false);
        parent::run();

        return ob_get_clean();
    }

    /**
     * Compute widget state (changed or not)
     *
     * @return bool
     */
    protected function computeLiveState()
    {
        $items = $this->links;
        $stateCheck = $this->checkWidgetState($items, true, true);
        if (is_bool($stateCheck)) {
            return $stateCheck;
        }

        return false;
    }
}