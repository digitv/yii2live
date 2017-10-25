<?php

namespace digitv\yii2live\components;

use digitv\yii2live\Yii2Live;

/**
 * Class Request
 * @package digitv\yii2live\components
 */
class Request extends \yii\web\Request
{
    protected $_isLiveUsed;

    /**
     * Check that request is made using async component
     * @return bool
     */
    public function isLiveUsed() {
        if(!isset($this->_isLiveUsed)) {
            $requestId = $this->getRequestId();
            $this->_isLiveUsed = !empty($requestId);
        }
        return $this->_isLiveUsed;
    }

    /**
     * Get ID of this request
     * @return array|null|string
     */
    public function getRequestId() {
        $component = Yii2Live::getSelf();
        $headerName = $component->headerName;
        $requestId = $this->headers->get($headerName);
        if(!$requestId || trim($requestId) === "") return null;
        return $requestId;
    }
}