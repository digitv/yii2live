<?php

namespace digitv\yii2live\components;

use Yii;
use yii\helpers\Url;
use digitv\yii2live\Yii2Live;

/**
 * Class JsCommand
 *
 * @property Response $response
 * @property array    $stack
 * @property array    $commands
 * @property array    $allCommands
 */
class JsCommand extends \yii\base\BaseObject implements ResponseObject
{
    const CMD_TYPE_JQUERY = 'jQuery';
    const CMD_TYPE_JQUERY_CHAIN = 'jQueryChain';
    const CMD_TYPE_LIVE = 'live';
    const CMD_TYPE_PJAX = 'pjax';
    const CMD_TYPE_MODAL = 'modal';
    const CMD_TYPE_MESSAGE = 'message';

    protected $chainActive = false;

    protected $chainData = [];

    protected $stack = [];

    protected static $instance;

    /**
     * $.fn.html
     *
     * @param  string $selector
     * @param  string $content
     * @return JsCommand
     */
    public function jHtml($selector = null, $content = '')
    {
        return $this->jQuery($selector, 'html', [$content]);
    }

    /**
     * $.fn.prepend
     *
     * @param  string|null $selector
     * @param  string      $content
     * @return JsCommand
     */
    public function jPrepend($selector = null, $content)
    {
        return $this->jQuery($selector, 'prepend', [$content]);
    }

    /**
     * $.fn.append
     *
     * @param  string|null $selector
     * @param  string      $content
     * @return JsCommand
     */
    public function jAppend($selector = null, $content)
    {
        return $this->jQuery($selector, 'append', [$content]);
    }

    /**
     * $.fn.before
     *
     * @param  string|null $selector
     * @param  string      $content
     * @return JsCommand
     */
    public function jBefore($selector = null, $content)
    {
        return $this->jQuery($selector, 'before', [$content]);
    }

    /**
     * $.fn.after
     *
     * @param  string|null $selector
     * @param  string      $content
     * @return JsCommand
     */
    public function jAfter($selector = null, $content)
    {
        return $this->jQuery($selector, 'after', [$content]);
    }

    /**
     * $.fn.replaceWith
     *
     * @param  string|null $selector
     * @param  string      $content
     * @return JsCommand
     */
    public function jReplaceWith($selector = null, $content)
    {
        return $this->jQuery($selector, 'replaceWith', [$content]);
    }

    /**
     * $.fn.addClass
     *
     * @param  string|null $selector
     * @param  string      $className
     * @return JsCommand
     */
    public function jAddClass($selector = null, $className)
    {
        return $this->jQuery($selector, 'addClass', [$className]);
    }

    /**
     * $.fn.removeClass
     *
     * @param  string|null $selector
     * @param  string      $className
     * @return JsCommand
     */
    public function jRemoveClass($selector = null, $className)
    {
        return $this->jQuery($selector, 'removeClass', [$className]);
    }

    /**
     * $.fn.attr
     * $cmd->jAttr('a', 'href', '/') OR
     * $cmd->jAttr('a', ['href' => '/'])
     *
     * @param  string|null       $selector
     * @param  string|null|array $attributeName
     * @param  string|null       $attributeValue
     * @return JsCommand
     */
    public function jAttr($selector = null, $attributeName = null, $attributeValue = null)
    {
        $arguments = [$attributeName];
        if (isset($attributeValue)) {
            $arguments[] = $attributeValue;
        }

        return $this->jQuery($selector, 'attr', $arguments);
    }

    /**
     * $.fn.removeAttr
     *
     * @param  string|null       $selector
     * @param  string|null|array $attributeName
     * @return JsCommand
     */
    public function jRemoveAttr($selector = null, $attributeName = null)
    {
        return $this->jQuery($selector, 'removeAttr', [$attributeName]);
    }

    /**
     * $.fn.data
     * $cmd->jData('a#link_1', 'pluginState', '2') OR
     * $cmd->jData('a#link_1', ['pluginState' => '2'])
     *
     * @param  string|null       $selector
     * @param  string|null|array $attributeName
     * @param  string|null       $attributeValue
     * @return JsCommand
     */
    public function jData($selector = null, $attributeName = null, $attributeValue = null)
    {
        $arguments = [$attributeName];
        if (isset($attributeValue)) {
            $arguments[] = $attributeValue;
        }

        return $this->jQuery($selector, 'data', $arguments);
    }

    /**
     * Begin jQuery chain
     *
     * @return JsCommand
     */
    public function chainBegin()
    {
        $this->chainActive = true;
        $this->chainData = [];

        return $this;
    }

    /**
     * End jQuery chain
     *
     * @return JsCommand
     */
    public function chainEnd()
    {
        $command = [
            'type' => static::CMD_TYPE_JQUERY_CHAIN,
            'commands' => $this->chainData,
        ];
        $this->chainActive = false;
        $this->chainData = [];

        return $this->addCommand($command);
    }

    /**
     * $.fn.parent (used in chain)
     *
     * @param  string $selector
     * @return JsCommand
     */
    public function jParent($selector)
    {
        return $this->jQuery($selector, 'parent', []);
    }

    /**
     * $.fn.parents (used in chain)
     *
     * @param  string      $selector
     * @param  string|null $filter
     * @return JsCommand
     */
    public function jParents($selector, $filter = null)
    {
        $arguments = isset($filter) ? [$filter] : [];

        return $this->jQuery($selector, 'parents', $arguments);
    }

    /**
     * $.fn.remove
     *
     * @param  string|null $selector
     * @return JsCommand
     */
    public function jRemove($selector = null)
    {
        return $this->jQuery($selector, 'remove');
    }

    /**
     * $.fn.hide
     *
     * @param  string|null $selector
     * @return JsCommand
     */
    public function jHide($selector = null)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->jQuery($selector, 'hide', $args);
    }

    /**
     * $.fn.show
     *
     * @param  string|null $selector
     * @return JsCommand
     */
    public function jShow($selector = null)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->jQuery($selector, 'show', $args);
    }

    /**
     * $.fn.fadeIn
     *
     * @param  string|null     $selector
     * @param  int|string|null $time
     * @return JsCommand
     */
    public function jFadeIn($selector = null, $time = null)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->jQuery($selector, 'fadeIn', $args);
    }

    /**
     * $.fn.fadeOut
     *
     * @param  string|null     $selector
     * @param  int|string|null $time
     * @return JsCommand
     */
    public function jFadeOut($selector = null, $time = null)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->jQuery($selector, 'fadeOut', $args);
    }

    /**
     * Invoke custom jQuery method for selector
     *
     * @param  string|null $selector
     * @param  string|null $method
     * @param  array       $arguments
     * @return JsCommand
     */
    public function jInvoke($selector = null, $method = null, $arguments = [])
    {
        return $this->jQuery($selector, $method, $arguments);
    }

    /**
     * jQuery command
     *
     * @param  string|null $selector
     * @param  string|null $method
     * @param  array       $arguments
     * @return JsCommand
     */
    public function jQuery($selector = null, $method = null, $arguments = [])
    {
        if (! isset($method) || (! isset($selector) && ! $this->chainActive)) {
            return $this;
        }
        $command = [
            'type' => static::CMD_TYPE_JQUERY,
            'selector' => $selector,
            'method' => $method,
            'args' => $arguments,
        ];

        return $this->addCommand($command);
    }

    /**
     * $.pjax.reload()
     *
     * @param  array|string $params
     * @return JsCommand
     */
    public function pjaxReload($params)
    {
        if (is_string($params)) {
            $params = ['container' => $params, 'timeout' => 10000];
        }
        if (! is_array($params)) {
            $params = (array)$params;
        }

        return $this->Pjax('reload', $params);
    }

    /**
     * Pjax command
     *
     * @param  string $method
     * @param  array  $params
     * @return JsCommand
     */
    public function Pjax($method, $params = [])
    {
        $command = [
            'type' => static::CMD_TYPE_PJAX,
            'method' => $method,
            'args' => [$params],
        ];

        return $this->addCommand($command);
    }

    /**
     * Reload current context (HtmlContainer or page)
     *
     * @param  int         $time      Time in milliseconds.
     * @param  string|null $contextId context ID
     * @return JsCommand
     */
    public function reloadContext($time = 250, $contextId = null)
    {
        $live = Yii2Live::getSelf();
        if (! isset($time)) {
            $time = 250;
        }
        if ($live->getContextType() === Yii2Live::CONTEXT_TYPE_EXACT || isset($contextId)) {
            $contextId = $contextId ?? $live->getContextId();
            $selector = '#' . $contextId;

            return $this->pjaxReload(['container' => $selector, 'time' => $time]);
        }

        return $this->live('utils.pageReload', [$time]);
    }

    /**
     * Redirect user to page
     *
     * @param  string|array $url
     * @param  int          $time
     * @return JsCommand
     */
    public function redirect($url, $time = 100)
    {
        if (is_array($url)) {
            $url = Url::to($url);
        }

        return $this->live('utils.pageRedirect', [$url, $time]);
    }

    /**
     * Yii2Live command
     *
     * @param  string $method
     * @param  array  $arguments
     * @return JsCommand
     */
    public function live($method, $arguments)
    {
        $command = [
            'type' => static::CMD_TYPE_LIVE,
            'method' => $method,
            'args' => $arguments,
        ];

        return $this->addCommand($command);
    }

    /**
     * Modal - change body
     *
     * @param  string      $content
     * @param  string|null $selector
     * @return JsCommand
     */
    public function modalBody($content, $selector = null)
    {
        return $this->modal($selector, 'body', [$content]);
    }

    /**
     * Modal - change title
     *
     * @param  string|null $title
     * @param  string|null $selector
     * @return JsCommand
     */
    public function modalTitle($title = null, $selector = null)
    {
        if (! isset($title) && isset(Yii::$app->view->title)) {
            $title = Yii::$app->view->title;
        } elseif (! isset($title)) {
            return $this;
        }

        return $this->modal($selector, 'title', [$title]);
    }

    /**
     * Modal - close
     *
     * @param  string|null $selector
     * @return JsCommand
     */
    public function modalClose($selector = null)
    {
        return $this->modal($selector, 'hide');
    }

    /**
     * Modal - open
     *
     * @param  string|null $selector
     * @return JsCommand
     */
    public function modalOpen($selector = null)
    {
        return $this->modal($selector, 'show');
    }

    /**
     * Bootstrap modal command
     *
     * @param  string      $selector
     * @param  string|null $method
     * @param  array       $arguments
     * @return JsCommand
     */
    public function modal($selector, $method = null, $arguments = [])
    {
        if (! isset($method)) {
            return $this;
        }
        $command = [
            'type' => static::CMD_TYPE_MODAL,
            'selector' => $selector,
            'method' => $method,
            'args' => $arguments,
        ];

        return $this->addCommand($command);
    }

    /**
     * Show status message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageStatus($text)
    {
        return $this->messageSuccess($text);
    }

    /**
     * Show error message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageError($text)
    {
        return $this->messageDanger($text);
    }

    /**
     * Show success message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageSuccess($text)
    {
        return $this->messageAdd($text, 'success');
    }

    /**
     * Show info message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageInfo($text)
    {
        return $this->messageAdd($text, 'info');
    }

    /**
     * Show warning message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageWarning($text)
    {
        return $this->messageAdd($text, 'warning');
    }

    /**
     * Show danger message
     *
     * @param  string $text
     * @return JsCommand
     */
    public function messageDanger($text)
    {
        return $this->messageAdd($text, 'danger');
    }

    /**
     * Show message to user
     *
     * @param  string $text
     * @param  string $type
     * @return JsCommand
     */
    public function messageAdd($text, $type = 'success')
    {
        $command = [
            'type' => static::CMD_TYPE_MESSAGE,
            'method' => 'show',
            'args' => [$text, $type],
        ];

        return $this->addCommand($command);
    }

    /**
     * Clear commands
     *
     * @return JsCommand
     */
    public function clearCommands()
    {
        $this->chainData = [];
        $this->chainActive = false;
        $this->stack = [];

        return $this;
    }

    /**
     * Get commands
     *
     * @param  array $commands
     * @return JsCommand
     */
    public function setCommands($commands = [])
    {
        $this->stack = $commands;

        return $this;
    }

    /**
     * Get commands
     *
     * @param  bool $checkChain
     * @return array
     */
    public function getCommands($checkChain = true)
    {
        if ($checkChain && $this->chainActive) {
            $this->chainEnd();
        }

        return $this->stack;
    }

    /**
     * Get all commands at the end of request
     *
     * @return array
     */
    public function getAllCommands()
    {
        $flashes = Yii::$app->session->getAllFlashes();
        if (! empty($flashes)) {
            foreach ($flashes as $flashKey => $flashGroup) {
                if (is_array($flashGroup) && ! empty($flashGroup)) {
                    foreach ($flashGroup as $messageText) {
                        $this->messageAdd($messageText, $flashKey);
                    }
                } elseif (is_string($flashGroup)) {
                    $this->messageAdd($flashGroup, $flashKey);
                }
            }
        }

        return $this->commands;
    }

    /**
     * Add command to the stack
     *
     * @param  array $command
     * @return JsCommand
     */
    public function addCommand($command = [])
    {
        if ($this->chainActive) {
            $this->chainData[] = $command;
        } else {
            $this->stack[] = $command;
        }

        return $this;
    }

    /**
     * Clear current jQuery chain
     */
    public function clearChain()
    {
        $this->chainData = [];
        $this->chainActive = false;
    }

    /**
     * Get instance of class
     * !!! DO NOT CONSTRUCT IT YOURSELF !!!
     *
     * @param  array $config
     * @return static
     */
    public static function getInstance($config = [])
    {
        if (! isset(static::$instance)) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }


    /**
     * Get data for Response
     *
     * @return array
     */
    public function getResponseData()
    {
        return [];
    }

    /**
     * Get Response format
     *
     * @return string
     */
    public function getResponseType()
    {
        return Response::FORMAT_JSON;
    }
}