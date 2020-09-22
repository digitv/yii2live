<?php

namespace digitv\yii2live\yii2sockets;

use digitv\yii2live\helpers\Html;
use digitv\yii2sockets\YiiNodeSocketFrameBasic;

/**
 * Class YiiNodeSocketFrameLoader
 *
 * @package digitv\yii2live\yii2sockets
 *
 * @property string $type
 */
class YiiNodeSocketFrameLoader extends YiiNodeSocketFrameBasic
{
    const TYPE_ADD_MESSAGE = 'addMessage';
    const TYPE_FINISH_MESSAGE = 'finishMessage';
    const TYPE_FLUSH_MESSAGES = 'flushMessages';

    public $progressMessageIconClass = 'fa fa-check';

    protected $_callback = 'yii2liveLoaderCallback';

    protected $_type;
    protected $_progressMessage;
    protected $_progressMessageKey;
    protected $_progressMessageFinished;

    /**
     * Add loader progress message
     *
     * @param  string|null $message
     * @param  string|null $key
     * @param  bool        $finished
     * @return static
     */
    public function addProgressMessage($message = null, $key = null, $finished = false)
    {
        if (! isset($message) || ! is_string($message) || trim($message) === "") {
            return $this;
        }
        if (! isset($key) || ! is_string($key) || trim($key) === "") {
            $key = 'progress-message-' . ceil(microtime(true) * 1000);
        }
        $this->_progressMessage = $message;
        $this->_progressMessageKey = $key;
        $this->_progressMessageFinished = $finished ? 1 : 0;
        $this->type = static::TYPE_ADD_MESSAGE;

        return $this;
    }

    /**
     * Mark progress message as finished
     *
     * @param  string $key
     * @return YiiNodeSocketFrameLoader
     */
    public function finishProgressMessage($key)
    {
        $this->_progressMessageKey = $key;
        $this->type = static::TYPE_FINISH_MESSAGE;

        return $this;
    }

    /**
     * Flush all messages
     *
     * @return YiiNodeSocketFrameLoader
     */
    public function flushMessages()
    {
        $this->type = static::TYPE_FLUSH_MESSAGES;

        return $this;
    }

    /**
     * Set frame subtype
     *
     * @param  string $type
     * @return YiiNodeSocketFrameLoader
     */
    public function setType($type = self::TYPE_ADD_MESSAGE)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * Get frame subtype
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Validate input
     *
     * @return bool
     */
    public function validate()
    {
        $this->composeOptions();

        return parent::validate();
    }

    /**
     * Compose frame options
     */
    public function composeOptions()
    {
        $body = [
            'type' => $this->_type,
        ];
        switch ($this->_type) {
            case static::TYPE_ADD_MESSAGE:
                $icon = Html::tag('i', '', ['class' => $this->progressMessageIconClass . ' icon']);
                $body['message'] = $this->_progressMessage . $icon;
                $body['messageKey'] = $this->_progressMessageKey;
                $body['messageFinished'] = $this->_progressMessageFinished;
                break;
            case static::TYPE_FINISH_MESSAGE:
                $body['messageKey'] = $this->_progressMessageKey;
                break;
            case static::TYPE_FLUSH_MESSAGES:
                break;
            default:
                return $this;
        }
        $this->setBody($body);

        return $this;
    }
}