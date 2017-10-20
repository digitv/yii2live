<?php

namespace digitv\yii2live\components;

use digitv\yii2live\assets\Yii2LiveAsset;
use digitv\yii2live\widgets\LoadingIndicator;
use digitv\yii2live\Yii2Live;
use Yii;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/**
 * Class View
 */
class View extends \yii\web\View
{
    const LIVE_POS_HEAD        = 'head';
    const LIVE_POS_BODY_BEGIN  = 'bodyBegin';
    const LIVE_POS_BODY_END    = 'bodyEnd';

    const LIVE_TYPE_CSS        = 'css';
    const LIVE_TYPE_JS         = 'js';
    const LIVE_TYPE_META       = 'meta';
    const LIVE_TYPE_LINKS      = 'links';
    const LIVE_TYPE_TITLE      = 'title';

    const LIVE_DATA_CALLBACK_TITLE     = 'processTitle';
    const LIVE_DATA_CALLBACK_JS        = 'processJs';
    const LIVE_DATA_CALLBACK_CSS       = 'processCss';
    const LIVE_DATA_CALLBACK_META      = 'processMeta';
    const LIVE_DATA_CALLBACK_LINKS     = 'processHeadLinks';
    const LIVE_DATA_CALLBACK_CSRF      = 'processCsrf';

    public $livePageMeta = [];
    public $livePageBlocks = [];

    /**
     * @inheritdoc
     */
    public function endBody()
    {
        Yii2LiveAsset::register($this);
        parent::endBody();
    }

    /**
     * @inheritdoc
     */
    public function endPage($ajaxMode = false)
    {
        $component = Yii2Live::getSelf();
        if($component->isLiveRequest()) {
            $this->endPageLive();
            return;
        }
        $this->trigger(self::EVENT_END_PAGE);

        $content = ob_get_clean();

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    public function endPageLive() {
        $this->trigger(self::EVENT_END_PAGE);

        $content = ob_get_clean();

        $this->livePageMeta = $this->getLivePageMeta();

        //echo 'xxx'; return [];

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml(true),
        ]);

        $this->clear();
    }

    /**
     * Get CSS, JS, meta tags, link tags, title, csrf token
     * @return array
     */
    public function getLivePageMeta() {
        $data = [];
        //Title
        if($this->title) {
            $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_TITLE, $this->title);
        }
        //CSRF
        $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_CSRF, Yii::$app->request->csrfParam, Yii::$app->request->csrfToken);
        //CSS
        $cssData = [];
        if (!empty($this->cssFiles)) {
            $cssData['items'] = $this->cssFiles;
            $cssData['order'] = array_keys($this->cssFiles);
        }
        if (!empty($this->css)) {
            $cssData['inline'] = implode("\n", $this->css);
        }
        if (!empty($cssData)) {
            $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_CSS, $cssData);
        }
        //Meta tags
        if (!empty($this->metaTags)) {
            $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_META, ['items' => $this->metaTags]);
        }
        //Link tags
        if (!empty($this->linkTags)) {
            $linksData = [
                'items' => $this->linkTags,
                'order' => array_keys($this->linkTags),
            ];
            $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_LINKS, $linksData);
        }
        //Javascript
        $dataJs = $this->getAsyncScripts();
        if(!empty($dataJs)) {
            $data = ArrayHelper::merge($data, $dataJs);
        }

        return $data;
    }

    /**
     * Get JS scripts
     * @return array the rendered content
     */
    protected function getAsyncScripts()
    {
        $data = [];
        //Head section
        $dataPos = [
            self::POS_HEAD => [
                'regionSelector' => 'head',
            ],
            self::POS_BEGIN => [
                'region' => self::getPageRegion(self::POS_BEGIN, 'js'),
            ],
            self::POS_END => [
                'region' => self::getPageRegion(self::POS_END, 'js'),
            ],
        ];

        //Head section
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $dataPos[self::POS_HEAD]['items'] = $this->jsFiles[self::POS_HEAD];
            $dataPos[self::POS_HEAD]['order'] = array_keys($this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $dataPos[self::POS_HEAD]['inline'] = Html::script(implode("\n", $this->js[self::POS_HEAD]), ['type' => 'text/javascript']);
        }

        //Body begin section
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $dataPos[self::POS_BEGIN]['order'] = array_keys($this->jsFiles[self::POS_BEGIN]);
            $dataPos[self::POS_BEGIN]['items'] = $this->jsFiles[self::POS_BEGIN];
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $dataPos[self::POS_BEGIN]['inline'] = Html::script(implode("\n", $this->js[self::POS_BEGIN]), ['type' => 'text/javascript']);
        }

        //Body end section
        if (!empty($this->jsFiles[self::POS_END])) {
            $dataPos[self::POS_END]['order'] = array_keys($this->jsFiles[self::POS_END]);
            $dataPos[self::POS_END]['items'] = $this->jsFiles[self::POS_END];
        }
        $inlineJsEnd = [];
        if (!empty($this->js[self::POS_END])) {
            $inlineJsEnd[] = Html::script(implode("\n", $this->js[self::POS_END]), ['type' => 'text/javascript']);
        }
        if (!empty($this->js[self::POS_READY])) {
            $js = "jQuery(document).ready(function () {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
            $inlineJsEnd[] = Html::script($js, ['type' => 'text/javascript']);
        }
        if (!empty($this->js[self::POS_LOAD])) {
            $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
            $inlineJsEnd[] = Html::script($js, ['type' => 'text/javascript']);
        }
        $dataPos[self::POS_END]['inline'] = !empty($inlineJsEnd) ? implode("\n", $inlineJsEnd) : '';

        foreach ($dataPos as $dataRow) {
            if(empty($dataRow) || count($dataRow) <= 1) continue;
            $data[] = self::buildJsProcessRow(self::LIVE_DATA_CALLBACK_JS, $dataRow);
        }

        return $data;
    }

    /**
     * Renders the content to be inserted at the beginning of the body section.
     * The content is rendered using the registered JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderBodyBeginHtml()
    {
        $lines = [];
        $regionFiles = self::getPageRegion(self::POS_BEGIN);
        $regionInline = self::getPageRegion(self::POS_BEGIN, 'js', true);
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BEGIN]);
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BEGIN]), ['type' => 'text/javascript']);
        }
        $linesStr = Html::tag('div', implode("\n", $lines), ['data-live-region' => $regionFiles]);

        return $linesStr;
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     * The content is rendered using the registered JS code blocks and files.
     * @param bool $ajaxMode whether the view is rendering in AJAX mode.
     * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
     * will be rendered at the end of the view like normal scripts.
     * @return string the rendered content
     */
    protected function renderBodyEndHtml($ajaxMode)
    {
        $lines = [];

        if ($ajaxMode) {
            if (!empty($this->jsFiles[self::POS_END])) {
                $lines[] = implode("\n", $this->jsFiles[self::POS_END]);
            }
            $scripts = [];
            if (!empty($this->js[self::POS_END])) {
                $scripts[] = implode("\n", $this->js[self::POS_END]);
            }
            if (!empty($this->js[self::POS_READY])) {
                $scripts[] = implode("\n", $this->js[self::POS_READY]);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $scripts[] = implode("\n", $this->js[self::POS_LOAD]);
            }
            if (!empty($scripts)) {
                $lines[] = Html::script(implode("\n", $scripts), ['type' => 'text/javascript']);
            }
        } else {
            $jsInline = [];
            $jsLines  = [];
            if (!empty($this->jsFiles[self::POS_END])) {
                //$temp = implode("\n", $this->jsFiles[self::POS_END]);
                //$lines[] = Html::tag('div', $temp, ['data-live-region' => self::getPageRegion(self::POS_END)]);
                $jsLines[] = implode("\n", $this->jsFiles[self::POS_END]);
            } else {
                //$lines[] = Html::tag('div', "\n", ['data-live-region' => self::getPageRegion(self::POS_END)]);
            }
            if (!empty($this->js[self::POS_END])) {
                $jsInline[] = Html::script(implode("\n", $this->js[self::POS_END]), ['type' => 'text/javascript']);
            }
            if (!empty($this->js[self::POS_READY])) {
                $js = "jQuery(document).ready(function () {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
                $jsInline[] = Html::script($js, ['type' => 'text/javascript']);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
                $jsInline[] = Html::script($js, ['type' => 'text/javascript']);
            }
            if(!empty($jsInline)) {
                $jsLines[] = implode("\n", $jsInline);
            }
            $lines[] = Html::tag('div', implode("\n", $jsLines), ['data-live-region' => self::getPageRegion(self::POS_END, 'js')]);
        }

        //Add loading indicator
        $lines[] = LoadingIndicator::widget([
            'id' => 'yii2-live-loader',
        ]);

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Registers a CSS code block.
     * @param string $css the content of the CSS code block to be registered
     * @param array $options the HTML attributes for the `<style>`-tag.
     * @param string $key the key that identifies the CSS code block. If null, it will use
     * $css as the key. If two CSS code blocks are registered with the same key, the latter
     * will overwrite the former.
     */
    public function registerCss($css, $options = [], $key = null)
    {
        $key = $key ?: md5($css);
        $options['data-async-key'] = $key;
        $this->css[$key] = Html::style($css, $options);
    }

    /**
     * Build data row for JS processing
     * @param string $callback
     * @param mixed $arg
     * @param mixed $arg2
     * @param mixed $argN
     * @return array
     */
    protected static function buildJsProcessRow($callback, $arg, $arg2 = null, $argN = null) {
        $args = func_get_args();
        array_shift($args);
        $data = [
            'callback' => $callback,
            'args' => $args,
        ];
        return $data;
    }

    /**
     * Get Yii2Live region name for JS/CSS
     * @param int    $viewPosition
     * @param string $type
     * @param bool   $inline
     * @return string
     */
    protected static function getPageRegion($viewPosition = self::POS_HEAD, $type = 'js', $inline = false) {
        $subtype = $inline ? 'inline' : 'files';
        return $viewPosition . '--' . $type . '-' . $subtype;
    }
}