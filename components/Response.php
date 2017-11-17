<?php

namespace digitv\yii2live\components;

use digitv\yii2live\Yii2Live;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Response
 * @package digitv\yii2live\components
 */
class Response extends \yii\web\Response
{
    public $livePageWidgets = [];

    public $liveCommands = [];

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->setAsyncFormat();
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepareAsync();
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }

    public function prepareAsync() {
        if(!$this->checkData()) return;
        $component = Yii2Live::getSelf();
        $responseData = $this->data;
        /** @var View $view */
        $view = Yii::$app->view;
        $jsCmd = $component->commands();
        $jsAttributes = $component->attributes()->getAttributesForJs();
        if(!empty($jsAttributes)) {
            foreach ($jsAttributes as $selector => $attributes) {
                $jsCmd->jAttr($selector, $attributes);
            }
        }
        $this->liveCommands = ArrayHelper::merge($this->liveCommands, $jsCmd->commands);
        $data = [
            'meta' => $view->livePageMeta,
            'widgets' => $this->livePageWidgets,
            'commands' => $this->liveCommands,
        ];
        if(is_array($responseData)) {
            $data['content'] = $responseData;
        } elseif ($this->data instanceof ResponseObject) {
            $data['content'] = $this->data->getResponseData();
            $this->data = [];
        } else {
            $data['contentHtml'] = $responseData;
        }
        $this->data = $data;
    }

    /**
     * Set Response format
     */
    public function setAsyncFormat() {
        if($this->checkData()) {
            $this->format = $this->data instanceof ResponseObject ? $this->data->getResponseType() : self::FORMAT_JSON;
        }
    }

    /**
     * Check that we can handle response
     * @return bool
     */
    protected function checkData() {
        $component = Yii2Live::getSelf();
        $isLiveRequest = $component->isLiveRequest();
        $isJsCommand = $this->data instanceof ResponseObject;
        return $isLiveRequest || $isJsCommand;
    }
}