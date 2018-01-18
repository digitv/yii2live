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
    /** @var array Not removable widgets */
    public $livePageWidgetsRequired = [];

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
        $this->prepareLive();
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }

    /**
     * Prepare response on `live` requests
     */
    public function prepareLive() {
        if(!$this->checkData()) return;
        $component = Yii2Live::getSelf();
        $responseData = $this->data;
        $jsCmd = $component->commands();
        $jsAttributes = $component->attributes()->getAttributesForJs();
        if(!empty($jsAttributes)) {
            foreach ($jsAttributes as $selector => $attributes) {
                $jsCmd->jAttr($selector, $attributes);
            }
        }
        $this->liveCommands = ArrayHelper::merge($this->liveCommands, $jsCmd->commands);
        $data = [
            '_info' => $this->getPageInfo(),
            'meta' => $this->getPageMeta(),
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
     * Get page info
     * @return array
     */
    protected function getPageInfo() {
        $data = [
            'url' => Yii::$app->request->url,
            'method' => strtolower(Yii::$app->request->method),
            'requestId' => Yii2Live::getSelf()->requestId,
            'contextType' => Yii2Live::getSelf()->getContextType(),
        ];
        return $data;
    }

    /**
     * Get View page meta data
     * @return array
     */
    protected function getPageMeta() {
        /** @var View $view */
        $view = Yii::$app->view;
        $pageMeta = isset($view->livePageMeta) ? $view->livePageMeta : [];
        if(!($this->data instanceof ResponseObject) && !isset($view->livePageMeta)) {
            $pageMeta = $view->getLivePageMeta();
        }
        return $pageMeta;
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