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