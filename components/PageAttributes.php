<?php

namespace digitv\yii2live\components;

use yii\bootstrap\Html;

/**
 * Class PageAttributes
 *
 */
class PageAttributes extends \yii\base\BaseObject
{
    protected static $instance;

    protected $_attributes = [];

    protected $_elementSelectors = [];

    protected $_elementOnCursor;

    /**
     * Get attributes
     *
     * @param  string|null $elementKey
     * @return array|mixed
     */
    public function getElementAttributes($elementKey = null)
    {
        if (! isset($elementKey)) {
            return $this->_attributes;
        }

        return $this->_attributes[$elementKey] ?? [];
    }

    /**
     * Get attribute for element
     *
     * @param  string $elementKey
     * @param  string $attribute
     * @return null
     */
    public function getElementAttribute($elementKey, $attribute)
    {
        return $this->_attributes[$elementKey][$attribute] ?? null;
    }

    /**
     * Select element for further actions
     *
     * @param  string      $elementKey
     * @param  string|null $selector
     * @return PageAttributes
     */
    public function element($elementKey, $selector = null)
    {
        $this->initElement(false, $elementKey);
        $this->_elementOnCursor = $elementKey;
        if (! isset($selector) && ! isset($this->_elementSelectors[$elementKey])) {
            $selector = $elementKey;
        }
        if (isset($selector)) {
            $this->_elementSelectors[$elementKey] = $selector;
        }

        return $this;
    }

    /**
     * Deselect element
     *
     * @return PageAttributes
     */
    public function closeCursor()
    {
        $this->_elementOnCursor = null;

        return $this;
    }

    /**
     * Add CSS class
     *
     * @param  string|array $className
     * @return $this
     */
    public function addClass($className)
    {
        Html::addCssClass($this->_attributes[$this->_elementOnCursor], $className);

        return $this;
    }

    /**
     * Set CSS class
     *
     * @param  string|null $className
     * @return PageAttributes
     */
    public function setClass($className = null)
    {
        return $this->setAttribute('class', $className);
    }

    /**
     * Set `id` attribute
     *
     * @param  string|null $id
     * @return null|string
     */
    public function setId($id = null)
    {
        return $this->setAttribute('id', $id);
    }

    /**
     * Get `id` attribute
     *
     * @return null|string
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * Remove attribute
     *
     * @param  string $attribute
     * @return PageAttributes
     */
    public function removeAttribute($attribute)
    {
        return $this->setAttribute($attribute, null);
    }

    /**
     * Set attribute value for element on cursor
     *
     * @param  string      $attribute
     * @param  string|null $value
     * @return PageAttributes
     */
    public function setAttribute($attribute, $value = null)
    {
        if (! isset($this->_elementOnCursor)) {
            return $this;
        }
        if (isset($value)) {
            $this->initAttribute($attribute);
            $this->_attributes[$this->_elementOnCursor][$attribute] = $value;
        } elseif (isset($this->_attributes[$this->_elementOnCursor][$attribute])) {
            unset($this->_attributes[$this->_elementOnCursor][$attribute]);
        }

        return $this;
    }

    /**
     * Set attributes for element on cursor
     *
     * @param  array $attributes
     * @return $this
     */
    public function setAttributes($attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        return $this;
    }

    /**
     * Get attribute for element on cursor
     *
     * @param  string $attribute
     * @return string|null
     */
    public function getAttribute($attribute)
    {
        $attributes = $this->getAttributes();

        return $attributes[$attribute] ?? null;
    }

    /**
     * Get attributes for element on cursor
     *
     * @return array|null|string
     */
    public function getAttributes()
    {
        if (! isset($this->_elementOnCursor)) {
            return [];
        }

        return $this->getElementAttributes($this->_elementOnCursor);
    }

    /**
     * Render element attributes
     *
     * @param  string|null $elementKey
     * @param  bool        $clearEmpty
     * @return null|string
     */
    public function renderAttributes($elementKey = null, $clearEmpty = true)
    {
        //Remove empty attributes
        if ($clearEmpty) {
            $this->removeEmptyAttributes();
        }
        $attributes = isset($elementKey) ? $this->getElementAttributes($elementKey) : $this->getAttributes();

        return ! empty($attributes) ? Html::renderTagAttributes($attributes) : null;
    }

    /**
     * Get attributes for javascript
     *
     * @return array
     */
    public function getAttributesForJs()
    {
        if (empty($this->_attributes)) {
            return [];
        }
        $attributes = [];
        foreach ($this->_attributes as $elementKey => $elementAttributes) {
            if (! isset($this->_elementSelectors[$elementKey]) || empty($elementAttributes)) {
                continue;
            }
            $selector = $this->_elementSelectors[$elementKey];
            $attributes[$selector] = $elementAttributes;
        }

        return $attributes;
    }

    /**
     * Remove empty attributes
     *
     * @param  string|null $elementKey
     */
    protected function removeEmptyAttributes($elementKey = null)
    {
        $elementKey = $elementKey ?? $this->_elementOnCursor;
        if (! isset($this->_attributes[$elementKey])) {
            return;
        }
        foreach ($this->_attributes[$elementKey] as $attribute => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            if (trim($value) === "") {
                unset($this->_attributes[$elementKey]);
            }
        }
    }

    /**
     * Initialize element array
     *
     * @param  bool        $force
     * @param  string|null $elementKey
     * @return $this
     */
    protected function initElement($force = false, $elementKey = null)
    {
        $elementKey = $elementKey ?? $this->_elementOnCursor;
        if (! isset($elementKey)) {
            return $this;
        }
        if ($force || ! isset($this->_attributes[$elementKey])) {
            $this->_attributes[$elementKey] = [];
        }

        return $this;
    }

    /**
     * Initialize attribute for element
     *
     * @param  string $attribute
     * @param  bool   $force
     * @param  null   $elementKey
     * @return $this
     */
    protected function initAttribute($attribute, $force = false, $elementKey = null)
    {
        $elementKey = $elementKey ?? $this->_elementOnCursor;
        if (! isset($elementKey)) {
            return $this;
        }
        $this->initElement(false, $elementKey);
        if ($force || ! isset($this->_attributes[$elementKey][$attribute])) {
            $this->_attributes[$elementKey][$attribute] = '';
        }

        return $this;
    }

    /**
     * Get instance of class
     *
     * @param  array $config
     * @return PageAttributes
     */
    public static function getInstance($config = [])
    {
        if (! isset(static::$instance)) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }
}