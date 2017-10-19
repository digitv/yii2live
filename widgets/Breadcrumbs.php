<?php
/**
 * Created by coder1.
 * Date: 19.10.17
 * Time: 11:32
 */

namespace digitv\yii2live\widgets;


use digitv\yii2live\behaviors\WidgetBehavior;

class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
//    public function behaviors()
//    {
//        return [
//            'breadCrumbs' => [
//                'class' => BaseLiveWidgetExtender::className(),
//                'extendClass' => \yii\widgets\Breadcrumbs::className()
//            ],
//        ];
//    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => WidgetBehavior::className(),
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $this->id = $this->options['id'];
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        ob_start();
        ob_implicit_flush(false);
        parent::run();
        $content = ob_get_clean();
        return $content;
    }
}