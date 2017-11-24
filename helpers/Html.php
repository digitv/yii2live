<?php

namespace digitv\yii2live\helpers;

use Yii;
use yii\bootstrap\Html as BootstrapHtml;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use digitv\yii2live\components\HtmlChain;

/**
 * Class Html
 */
class Html extends BootstrapHtml
{
    /**
     * Generates a hyperlink tag.
     * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     * such as an image tag. If this is coming from end users, you should consider [[encode()]]
     * it to prevent XSS attacks.
     * @param array|string|null $url the URL for the hyperlink tag. This parameter will be processed by [[Url::to()]]
     * and will be used for the "href" attribute of the tag. If this parameter is null, the "href" attribute
     * will not be generated.
     *
     * If you want to use an absolute url you can call [[Url::to()]] yourself, before passing the URL to this method,
     * like this:
     *
     * ```php
     * Html::a('link text', Url::to($url, true))
     * ```
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string|HtmlChain the generated hyperlink
     * @see \yii\helpers\Url::to()
     */
    public static function a($text, $url = null, $options = [])
    {
        if ($url !== null) {
            $options['href'] = Url::to($url);
        }
        $config = ['tag' => 'a', 'tagContent' => $text, 'options' => $options];
        return static::createChain(HtmlChain::TYPE_LINK, $config);
    }

    /**
     * Generates a form start tag.
     * @param array|string $action the form action URL. This parameter will be processed by [[Url::to()]].
     * @param string $method the form submission method, such as "post", "get", "put", "delete" (case-insensitive).
     * Since most browsers only support "post" and "get", if other methods are given, they will
     * be simulated using "post", and a hidden input will be added which contains the actual method type.
     * See [[\yii\web\Request::methodParam]] for more details.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     *
     * Special options:
     *
     *  - `csrf`: whether to generate the CSRF hidden input. Defaults to true.
     *
     * @return string the generated form start tag.
     * @see endForm()
     */
    public static function beginForm($action = '', $method = 'post', $options = [])
    {
        $action = Url::to($action);

        $hiddenInputs = [];

        $request = Yii::$app->getRequest();
        if ($request instanceof \yii\web\Request) {
            if (strcasecmp($method, 'get') && strcasecmp($method, 'post')) {
                // simulate PUT, DELETE, etc. via POST
                $hiddenInputs[] = static::hiddenInput($request->methodParam, $method);
                $method = 'post';
            }
            $csrf = ArrayHelper::remove($options, 'csrf', true);

            if ($csrf && $request->enableCsrfValidation && strcasecmp($method, 'post') === 0) {
                $hiddenInputs[] = static::hiddenInput($request->csrfParam, $request->getCsrfToken());
            }
        }

        if (!strcasecmp($method, 'get') && ($pos = strpos($action, '?')) !== false) {
            // query parameters in the action are ignored for GET method
            // we use hidden fields to add them back
            foreach (explode('&', substr($action, $pos + 1)) as $pair) {
                if (($pos1 = strpos($pair, '=')) !== false) {
                    $hiddenInputs[] = static::hiddenInput(
                        urldecode(substr($pair, 0, $pos1)),
                        urldecode(substr($pair, $pos1 + 1))
                    );
                } else {
                    $hiddenInputs[] = static::hiddenInput(urldecode($pair), '');
                }
            }
            $action = substr($action, 0, $pos);
        }

        $options['action'] = $action;
        $options['method'] = $method;
        if (!empty($hiddenInputs)) {
            $options['tagContent'] .= "\n" . implode("\n", $hiddenInputs);
        }
        $form = static::beginTag('form', $options, true);

        return $form;
    }

    /**
     * Generates a start tag.
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string the generated start tag
     * @param bool $chain return HtmlChain
     * @see endTag()
     * @see tag()
     */
    public static function beginTag($name, $options = [], $chain = false)
    {
        if(!$chain) return parent::beginTag($name, $options);
        $config = [
            'tag' => $name,
            'options' => $options,
            'tagContent' => isset($options['tagContent']) ? $options['tagContent'] : '',
        ];
        return static::createChain(HtmlChain::TYPE_BEGIN_TAG, $config);
    }

    /**
     * Composes icon HTML for FontAwesome.
     * @param string $name icon short name, for example: 'star'
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. There are also a special options:
     *
     * - tag: string, tag to be rendered, by default 'i' is used.
     * - prefix: string, prefix which should be used to compose tag class, by default 'glyphicon glyphicon-' is used.
     *
     * @return string icon HTML.
     * @see http://fontawesome.io/
     */
    public static function icon($name, $options = [])
    {
        $tag = ArrayHelper::remove($options, 'tag', 'i');
        $classPrefix = ArrayHelper::remove($options, 'prefix', 'fa fa-');
        static::addCssClass($options, $classPrefix . $name);
        return static::tag($tag, '', $options);
    }

    /**
     * Composes icon HTML for Glyphicons.
     * @param string $name
     * @param array $options
     * @return string
     */
    public static function iconGlyph($name, $options = []) {
        return parent::icon($name, $options);
    }

    /**
     * Create HtmlChain object
     * @param string $type
     * @param array $config
     * @return HtmlChain
     */
    protected static function createChain($type = HtmlChain::TYPE_LINK, $config = []) {
        $chain = new HtmlChain(['type' => $type]);
        $chain->load($config);
        return $chain;
    }
}