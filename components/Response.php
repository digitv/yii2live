<?php

namespace digitv\yii2live\components;

use digitv\yii2live\Yii2Live;
use Yii;

/**
 * Class Response
 * @package digitv\yii2live\components
 */
class Response extends \yii\web\Response
{
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
        $async = Yii2Live::getSelf();
        if(!$async->isLiveRequest()) return;
        $responseData = $this->data;
        /** @var View $view */
        $view = Yii::$app->view;
        $data = [
            'meta' => $view->livePageMeta,
            'blocks' => $view->livePageBlocks,
        ];
        if(is_array($responseData)) {
            $data['content'] = $responseData;
        } else {
            $data['contentHtml'] = $responseData;
        }
        $this->data = $data;
    }

    public function setAsyncFormat() {
        $async = Yii2Live::getSelf();
        if($async->isLiveRequest()) {
            $this->format = self::FORMAT_JSON;
        }
    }
}