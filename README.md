# Yii2live SANDBOX project!

This extension helps you with AJAX links, forms and modals.

Application AJAX response is divided into:

* JS/CSS files;
* inline JS/CSS;
* Page widgets output;
* JS commands.

And all of these things are processed separately. 
So you can return only few javascript commands and no more else, 
or just one widget (as old good Pjax), 
or whole page, but only widgets, state of those were changed.

For example your `Nav` widget can decide what to do by itself - render widget fully or just change active link. There are special widget states for this.

## Creating AJAX link
```
echo \digitv\yii2live\helpers\Html::a('Ajax link', ['action'])
    ->ajax(true)
    ->context(Yii2Live::CONTEXT_TYPE_PARTIAL)
    ->pushState(false)
    ->requestMethod('post')
    ->confirm('Confirm message');
```

* Link is AJAX enabled (`ajax(true)`),
* using a `partial` context `context(Yii2Live::CONTEXT_TYPE_PARTIAL)`,
* page url is not changed on click (`->pushState(false)`),
* request method is POST (`requestMethod('post')`),
* and using confirm message (`confirm('Confirm message')`).

## Creating HtmlContainer like a Pjax
```
...
<?php \digitv\yii2live\widgets\PjaxContainer::begin(['id' => 'test-wrapper']) ?>
    <?= \yii\grid\GridView::widget([
        ...
    ]) ?>
<?php \digitv\yii2live\widgets\PjaxContainer::end() ?>
...
```
All links inside this container will be AJAX enabled. Search form, that will update this container is looks like this:
```
<?php $form = ActiveForm::begin([
    'id' => 'test-search-form',
    'action' => ['index'],
    'method' => 'get',
    'options' => [
        'data-live-context' => 'test-wrapper',
    ],
]); ?>
...
<?php ActiveForm::end(); ?>
```

## Using JS commands

```
...
    public function actionTest() {
        $live = Yii2Live::getSelf();
        $content = $this->render('test');
        if($live->isLiveRequest()) {
            $cmd = $live->commands();
            return $cmd->jHtml('#insert-selector', '<div>New HTML!</div>')
                ->jRemove('#remove-selector')
                ->modalBody($content)
                ->modalTitle('Modal title')
                ->messageSuccess('Success message!');
        } else {
            return $content;
        }
    }
...
```
* jHtml - jQuery.html()
* jRemove - jQuery.remove()
* modalBody - Set modal body content
* modalTitle - Set modal title content
* messageSuccess - Show success message to user

There are much more JS commands that you can use (@see in `digitv\yii2live\components\JsCommand`)

### _Config options_

|Option                 |Description|
|---                    |---        |
|enable                 |_Global enabled flag_|
|enableLiveLoad         |_Enable "live" request for each link and form_|
|enableReplaceAnimation |_Enable replace animation for each widget_|
|enableLoadingOverlay   |_Enable loading animation as animated overlay_|
|headerName             |_Header name used for "request ID" sending_|
|headerNameContext      |_Header name used for "context ID" sending_|
|linkSelector           |_jQuery selector for links (only when `enableLiveLoad` active)_|
|linkSelectorAjax       |_jQuery selector for links_|
|formSelector           |_jQuery selector for forms (only when `enableLiveLoad` active)_|
|formSelectorAjax       |_jQuery selector for forms_|
|messageAdapter         |_Name of message adapter. Used to show messages for user (`alert` or `notify`)_|
|modalDefaultSelector   |_jQuery selector for default modal popup_|