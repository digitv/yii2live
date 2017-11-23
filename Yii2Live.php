<?php

namespace digitv\yii2live;

use digitv\yii2live\behaviors\WidgetBehavior;
use digitv\yii2live\components\JsCommand;
use digitv\yii2live\components\PageAttributes;
use digitv\yii2live\components\Request;
use digitv\yii2live\components\Response;
use digitv\yii2live\components\View;
use digitv\yii2live\widgets\BaseLiveWidget;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class Yii2Async
 *
 * @property string $requestId
 */
class Yii2Live extends Component implements BootstrapInterface
{
    const SESSION_WIDGETS_KEY = 'yii2live-widgets-data';

    const CONTEXT_TYPE_PAGE     = 'page';
    const CONTEXT_TYPE_MODAL    = 'modal';
    const CONTEXT_TYPE_EXACT    = 'exact';
    //used only for JS
    const CONTEXT_TYPE_PARENT   = 'parent';

    /** @var bool Global enabled flag */
    public $enable = true;
    /** @var bool Enable page loading by ajax */
    public $enableLiveLoad = true;
    /** @var string Header name used for AJAX requests */
    public $headerName = 'X-Yii2-Live';
    /** @var string Header name used for AJAX request context */
    public $headerNameContext = 'X-Yii2-Live-Context';
    /** @var string Links selector for javascript code */
    public $linkSelector = 'a';
    /** @var string Links selector for javascript code (default, when live load disabled, to handle AJAX commands) */
    public $linkSelectorAjax = 'a[data-live-context], a[data-live-enabled]';
    /** @var string Forms selector for javascript code */
    public $formSelector = 'form';
    /** @var string Forms selector for javascript code (default, when live load disabled) */
    public $formSelectorAjax = 'form[data-live-context], form[data-live-enabled]';
    /** @var bool Enable replacing elements animation */
    public $enableReplaceAnimation = false;
    /** @var bool Enable replacing elements animation */
    public $enableLoadingOverlay = true;
    /** @var string User messages adapter */
    public $messageAdapter = 'alert';
    /** @var string Default modal selector */
    public $modalDefaultSelector = '#modal-default';

    /** @var bool Use Node.js sockets to send response */
    public $useNodeJsTransport = false;

    /** @var bool */
    protected $_isLiveRequest;
    /** @var string */
    protected $_requestId;
    /** @var string */
    protected $_requestContextType;
    /** @var string */
    protected $_requestContext;
    /** @var self */
    protected static $self;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if(!isset($_SESSION[static::SESSION_WIDGETS_KEY])) $_SESSION[static::SESSION_WIDGETS_KEY] = [];
        static::$self = $this;
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if(!$this->enable) return;
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
     * Check that request is performed by yii2live component
     * @return bool
     */
    public function isLiveRequest() {
        if(!isset($this->_isLiveRequest)) {
            $request = Yii::$app->request;
            $this->_isLiveRequest = $request instanceof Request && $request->isLiveUsed();
        }
        return $this->_isLiveRequest;
    }

    /**
     * Set widget data for response
     * @param BaseLiveWidget|WidgetBehavior $widget
     */
    public function setWidgetData($widget) {
        $widgetId = $widget instanceof BaseLiveWidget ? $widget->id : $widget->owner->id;
        $data = $widget->getWidgetLiveData();
        /** @var Response $response */
        $response = Yii::$app->response;
        if($widget->widgetType === WidgetBehavior::LIVE_WIDGET_TYPE_COMMANDS && !empty($data['data']) && is_array($data['data'])) {
            $response->liveCommands = ArrayHelper::merge($response->liveCommands, $data['data']);
        }
        $response->livePageWidgets[$widgetId] = $data;
    }

    /**
     * Get widget state for request
     * @param string $widgetId
     * @param bool   $raw
     * @return array|null
     */
    public function getWidgetRequestState($widgetId, $raw = false) {
        $requestId = $this->requestId;
        $data = $_SESSION[static::SESSION_WIDGETS_KEY];
        if(!isset($data[$requestId]) || !isset($data[$requestId][$widgetId])) return null;
        return $raw ? $data[$requestId][$widgetId] : $data[$requestId][$widgetId]['data'];
    }

    /**
     * Set widget state for request
     * @param string $widgetId
     * @param array $data
     */
    public function setWidgetRequestState($widgetId, $data) {
        $widgetData = [
            'data' => $data,
            'hash' => md5(json_encode($data, JSON_FORCE_OBJECT + JSON_UNESCAPED_UNICODE)),
        ];
        $_SESSION[static::SESSION_WIDGETS_KEY][$this->requestId][$widgetId] = $widgetData;
    }

    /**
     * Get request ID
     * @return string
     */
    public function getRequestId() {
        if(!isset($this->_requestId)) {
            $requestId = $this->getRequestHeaderId();
            if(!$requestId) {
                $time = ceil(microtime(true) . 1000);
                $rand = rand(1000, 9999);
                $requestId = md5($time . ':' . $rand);
            }
            $this->_requestId = $requestId;
        }
        return $this->_requestId;
    }

    /**
     * Get context type
     * @return string
     */
    public function getContextType() {
        if(!isset($this->_requestContextType)) {
            /** @var Request $request */
            $request = Yii::$app->request;
            $context = $request->getRequestContext();
            if(in_array($context, [static::CONTEXT_TYPE_PAGE, static::CONTEXT_TYPE_MODAL])) {
                $this->_requestContextType = $context;
            } else {
                $this->_requestContextType = static::CONTEXT_TYPE_EXACT;
            }
        }
        return $this->_requestContextType;
    }

    /**
     * Get context ID
     * @return null|string
     */
    public function getContextId() {
        if(!isset($this->_requestContext)) {
            /** @var Request $request */
            $request = Yii::$app->request;
            $contextType = $this->getContextType();
            if($contextType === static::CONTEXT_TYPE_EXACT) $this->_requestContext = $request->getRequestContext();
            else $this->_requestContext = false;
        }
        return $this->_requestContext !== false ? $this->_requestContext : null;
    }

    /**
     * Add commands to response
     * @return JsCommand
     */
    public function commands() {
        return JsCommand::getInstance();
    }

    /**
     * Set or get elements attributes in view
     * @return PageAttributes
     */
    public function attributes() {
        return PageAttributes::getInstance();
    }

    /**
     * Get request ID from header
     * @return array|null|string
     */
    protected function getRequestHeaderId() {
        $request = Yii::$app->request;
        if($request instanceof Request && $request->isLiveUsed()) {
            return $request->getRequestId();
        }
        return null;
    }

    /**
     * Get component without name
     * @return Yii2Live
     */
    public static function getSelf() {
        return static::$self;
    }
}