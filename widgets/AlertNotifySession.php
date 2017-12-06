<?php

namespace digitv\yii2live\widgets;

use Yii;
use yii\bootstrap\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class AlertNotifySession
 */
class AlertNotifySession extends Widget
{
    public static $counter = 0;
    public static $autoIdPrefix = 'alert-notify-session-';

    public $messages = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $sessionMessages = Yii::$app->session->getAllFlashes(true);
        foreach ($sessionMessages as $key => $messages) {
            if(!isset($this->messages[$key])) $this->messages[$key] = [];
            if(!is_array($messages)) $messages = [$messages];
            $this->messages[$key] = ArrayHelper::merge($this->messages[$key], $messages);
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $output = '';
        foreach ($this->messages as $key => $messages) {
            foreach ($messages as $message) {
                $result = AlertNotify::widget([
                    'type' => $key,
                    'message' => $message,
                ]);
                if(!empty($result)) $output .= (string)$result;
            }
        }
        return !empty($output) ? $output : null;
    }
}