<?php

namespace digitv\yii2live;

use digitv\yii2live\components\Request;
use digitv\yii2live\components\Response;
use digitv\yii2live\components\View;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * Class Yii2Async
 * @package digitv\yii2async
 */
class Yii2Live extends Component implements BootstrapInterface
{
    /** @var bool Global enabled flag */
    public $enabled = true;
    /** @var bool Use Node.js sockets to send response */
    public $useNodeJsTransport = false;
    /** @var string Links selector for javascript code */
    public $linkSelector = 'a';
    /** @var string Forms selector for javascript code */
    public $formSelector = 'form';
    /** @var string Header name used for AJAX requests */
    public $headerName = 'X-Yii2-Live';
    /** @var self */
    protected static $self;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        static::$self = $this;
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if(!$this->enabled) return;
        Yii::setAlias('@yii2live', __DIR__);
        $components = $app->getComponents(true);
        $requestDefinition = isset($components['request']) ? $components['request'] : [];
        $responseDefinition = isset($components['response']) ? $components['response'] : [];
        $viewDefinition = isset($components['view']) ? $components['view'] : [];
        $requestDefinition['class'] = Request::className();
        $responseDefinition['class'] = Response::className();
        $viewDefinition['class'] = View::className();
        $app->setComponents([
            'response' => $responseDefinition,
            'request' => $requestDefinition,
            'view' => $viewDefinition,
        ]);
    }

    /**
     * Check that request is performed by async component
     * @return bool
     */
    public function isLiveRequest() {
        $request = Yii::$app->request;
        return $request instanceof Request && $request->isLiveUsed();
    }

    /**
     * Get component without name
     * @return Yii2Live
     */
    public static function getSelf() {
        return static::$self;
    }
}