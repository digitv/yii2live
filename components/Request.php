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

    /**
     * Get request context (Yii2Live::CONTEXT_TYPE_PAGE || Yii2Live::CONTEXT_TYPE_MODAL || Element ID/exact context)
     * @return string
     */
    public function getRequestContext() {
        $component = Yii2Live::getSelf();
        $headerName = $component->headerNameContext;
        $requestContext = $this->headers->get($headerName);
        if(!$requestContext || !is_string($requestContext) || trim($requestContext) === "") return Yii2Live::CONTEXT_TYPE_PAGE;
        return $requestContext;
    }
}