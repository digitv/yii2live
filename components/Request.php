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
            $component = Yii2Live::getSelf();
            $headerName = $component->headerName;
            $this->_isLiveUsed = $this->headers->get($headerName) === 'true';
        }
        return $this->_isLiveUsed;
    }
}