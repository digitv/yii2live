<?php

namespace digitv\yii2live\components;

use Yii;
use yii\base\Object;

/**
 * Class JsCommand
 *
 * @property Response $response
 * @property array $stack
 * @property array $commands
 */
class JsCommand extends Object
{
    const CMD_TYPE_JQUERY           = 'jQuery';
    const CMD_TYPE_JQUERY_CHAIN     = 'jQueryChain';
    const CMD_TYPE_LIVE             = 'live';

    protected $chainActive = false;

    protected $chainData = [];

    protected $stack = [];

    protected static $instance;

    /**
     * $.fn.html
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jHtml($selector = null, $content = '') {
        return $this->jQuery($selector, 'html', [$content]);
    }

    /**
     * $.fn.prepend
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jPrepend($selector = null, $content) {
        return $this->jQuery($selector, 'prepend', [$content]);
    }

    /**
     * $.fn.append
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jAppend($selector = null, $content) {
        return $this->jQuery($selector, 'append', [$content]);
    }

    /**
     * $.fn.before
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jBefore($selector = null, $content) {
        return $this->jQuery($selector, 'before', [$content]);
    }

    /**
     * $.fn.after
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jAfter($selector = null, $content) {
        return $this->jQuery($selector, 'after', [$content]);
    }

    /**
     * $.fn.replaceWith
     * @param string $selector
     * @param string $content
     * @return JsCommand
     */
    public function jReplaceWith($selector = null, $content) {
        return $this->jQuery($selector, 'replaceWith', [$content]);
    }

    /**
     * $.fn.addClass
     * @param string $selector
     * @param string $className
     * @return JsCommand
     */
    public function jAddClass($selector = null, $className) {
        return $this->jQuery($selector, 'addClass', [$className]);
    }

    /**
     * $.fn.removeClass
     * @param string $selector
     * @param string $className
     * @return JsCommand
     */
    public function jRemoveClass($selector = null, $className) {
        return $this->jQuery($selector, 'removeClass', [$className]);
    }

    /**
     * $.fn.attr
     * $cmd->jAttr('a', 'href', '/') OR
     * $cmd->jAttr('a', ['href' => '/'])
     * @param string $selector
     * @param string|array $attributeName
     * @param string $attributeValue
     * @return JsCommand
     */
    public function jAttr($selector = null, $attributeName = null, $attributeValue = null) {
        $arguments = [$attributeName];
        if(isset($attributeValue)) $arguments[] = $attributeValue;
        return $this->jQuery($selector, 'attr', $arguments);
    }

    /**
     * Begin jQuery chain
     * @return JsCommand
     */
    public function chainBegin() {
        $this->chainActive = true;
        $this->chainData = [];
        return $this;
    }

    /**
     * End jQuery chain
     * @return JsCommand
     */
    public function chainEnd() {
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
     * @param $selector
     * @return JsCommand
     */
    public function jParent($selector) {
        return $this->jQuery($selector, 'parent', []);
    }

    /**
     * $.fn.parents (used in chain)
     * @param $selector
     * @return JsCommand
     */
    public function jParents($selector) {
        return $this->jQuery($selector, 'parents', ['li']);
    }

    /**
     * $.fn.remove
     * @param string $selector
     * @return JsCommand
     */
    public function jRemove($selector = null) {
        return $this->jQuery($selector, 'remove');
    }

    /**
     * $.fn.hide
     * @param string $selector
     * @return JsCommand
     */
    public function jHide($selector = null) {
        $args = func_get_args();
        array_shift($args);
        return $this->jQuery($selector, 'remove', $args);
    }

    /**
     * $.fn.show
     * @param string $selector
     * @return JsCommand
     */
    public function jShow($selector = null) {
        $args = func_get_args();
        array_shift($args);
        return $this->jQuery($selector, 'show', $args);
    }

    /**
     * $.fn.fadeIn
     * @param string $selector
     * @param int|string $time
     * @return JsCommand
     */
    public function jFadeIn($selector = null, $time = null) {
        $args = func_get_args();
        array_shift($args);
        return $this->jQuery($selector, 'fadeIn', $args);
    }

    /**
     * $.fn.fadeOut
     * @param string $selector
     * @param int|string $time
     * @return JsCommand
     */
    public function jFadeOut($selector = null, $time = null) {
        $args = func_get_args();
        array_shift($args);
        return $this->jQuery($selector, 'fadeOut', $args);
    }

    /**
     * Invoke custom jQuery method for selector
     * @param string $selector
     * @param string $method
     * @param array  $arguments
     * @return JsCommand
     */
    public function jInvoke($selector = null, $method = null, $arguments = []) {
        return $this->jQuery($selector, $method, $arguments);
    }

    /**
     * jQuery command
     * @param string $selector
     * @param string $method
     * @param array  $arguments
     * @return JsCommand
     */
    public function jQuery($selector = null, $method = null, $arguments = []) {
        if(!isset($method) || (!isset($selector) && !$this->chainActive)) {
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
     * Yii2Live command
     * @param string $method
     * @param array  $arguments
     * @return JsCommand
     */
    public function live($method, $arguments) {
        $command = [
            'type' => static::CMD_TYPE_LIVE,
            'method' => $method,
            'args' => $arguments,
        ];
        return $this->addCommand($command);
    }

    /**
     * Clear commands
     * @return JsCommand
     */
    public function clearCommands() {
        $this->chainData = [];
        $this->chainActive = false;
        $this->stack = [];
        return $this;
    }

    /**
     * Get commands
     * @param array $commands
     * @return JsCommand
     */
    public function setCommands($commands = []) {
        $this->stack = $commands;
        return $this;
    }

    /**
     * Get commands
     * @param bool $checkChain
     * @return array
     */
    public function getCommands($checkChain = true) {
        if($checkChain && $this->chainActive) {
            $this->chainEnd();
        }
        return $this->stack;
    }

    /**
     * Add command to the stack
     * @param array $command
     * @return JsCommand
     */
    public function addCommand($command = []) {
        if($this->chainActive) $this->chainData[] = $command;
        else $this->stack[] = $command;
        return $this;
    }

    /**
     * Clear current jQuery chain
     */
    public function clearChain() {
        $this->chainData = [];
        $this->chainActive = false;
    }

    /**
     * Get instance of class
     * !!! DO NOT CONSTRUCT IT YOURSELF !!!
     * @param array $config
     * @return static
     */
    public static function getInstance($config = []) {
        if(!isset(static::$instance)) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }
}