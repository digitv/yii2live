if (!Date.now) { Date.now = function() { return new Date().getTime(); } }

(function (global, factory) {
    global.Yii2Live = factory();
})(this, function () {

    var ylSettingsUtils, ylRequest, ylResponse, ylUtils, ylCallbacks, ylDom, ylPageMeta, ylPageWidgets, ylLoader, ylInit;

    //Settings utilities
    ylSettingsUtils = function (plugin) {
        return {
            get: function (key) {
                if(typeof key === "string") {
                    return typeof plugin.settings[key] !== "undefined" ? plugin.settings[key] : undefined;
                }
                return plugin.settings;
            },
            merge: function (settingsNew) {
                if(typeof settingsNew !== "object") return;
                plugin.settings = $.extend({}, plugin.settingsDefault, plugin.settings, settingsNew);
            }
        };
    };

    //Request
    ylRequest = function (plugin) {
        return {
            ajaxRequest: null,
            defaultOptions: {
                method: 'post',
                headers: {},
                success: plugin.callbacks.ajaxSuccess,
                error: plugin.callbacks.ajaxError,
                complete: plugin.callbacks.ajaxComplete,
                beforeSend: plugin.callbacks.ajaxBeforeSend
            },
            getDefaultOptions: function () {
                var options = $.extend({}, this.defaultOptions);
                options.headers[plugin.settings.headerName] = plugin.settings.requestId;
                return options;
            },
            ajax: function (url, options, element) {
                var elementOptions;
                if(typeof url === "undefined") {
                    console.error('Required parameter `url` is missing'); return;
                }
                plugin.request.ajaxAbort();
                plugin.loader.activate();
                if(typeof element !== "undefined") plugin.activeElement = element;
                elementOptions = plugin.utils.getElementOptions(element);
                if(typeof options.method === "undefined") {
                    options.method = elementOptions.method;
                }
                options = $.extend({}, this.getDefaultOptions(), options);
                options.headers[plugin.settings.headerNameContext] = elementOptions.context;
                //Set contentType and processData to false if FormData passed as `data` parameter
                if(typeof options.data !== "undefined" && options.data.toString() === '[object FormData]') {
                    $.extend(options, {
                        contentType: false,
                        processData: false
                    });
                }
                this.ajaxRequest = $.ajax(url, options);
            },
            ajaxAbort: function () {
                if(this.ajaxRequest) {
                    this.ajaxRequest.abort();
                    this.ajaxRequest = null;
                }
                plugin.loader.deActivate(true);
            },
            pushState: function (data, title, url, replace) {
                if(!plugin.utils.isPushStateSupported()) return;
                replace = typeof replace === "undefined" ? false : replace;
                if(window.location.pathname === url) replace = true;
                if(replace)
                    return history.replaceState(data, title, url);
                else
                    return history.pushState(data, title, url);
            }
        };
    };

    //Response
    ylResponse = function (plugin) {
        return {
            processData: function (data) {
                console.log(data);
                //Request info
                if(typeof data._info !== "undefined") {
                    if(typeof data._info.requestId !== "undefined" && plugin.settings.requestId !== data._info.requestId) {
                        plugin.settingsUtils.merge({requestId: data._info.requestId});
                    }
                }
                //Page meta info (JS/CSS/meta tags)
                if(typeof data.meta !== "undefined") {
                    plugin.pageMeta.process(data.meta);
                }
                //Page widgets
                if(typeof data.widgets !== "undefined") {
                    plugin.pageWidgets.process(data.widgets);
                }
                //JS commands
                if(typeof data.commands !== "undefined") {
                    plugin.ajaxCmd.process(data.commands);
                }
            }
        };
    };

    //Utilities
    ylUtils = function (plugin) {
        return {
            isLocalUrl: function (url) {
                url = $.trim(url);
                if(url === "/" || url === "") return true;
                if(new RegExp('^\/(?!\/)').test(url)) return true;
                if(typeof plugin.settings.domainsLocal !== "undefined" && plugin.settings.domainsLocal.length) {
                    for (var i in plugin.settings.domainsLocal) {
                        if(url.indexOf(plugin.settings.domainsLocal[i]) === 0) return true;
                    }
                }
                return false;
            },
            isPushStateSupported: function () {
                if(typeof plugin.pushStateSupported === "undefined") {
                    plugin.pushStateSupported = !!(history && history.pushState);
                }
                return plugin.pushStateSupported;
            },
            getElementActive: function () {
                return typeof plugin.activeElement !== "undefined" && plugin.activeElement.length ? plugin.activeElement : undefined;
            },
            setElementActive: function (element) {
                if(typeof element !== "undefined" && element.length) plugin.activeElement = element;
                else plugin.activeElement = undefined;
            },
            //Get element live option value
            getElementOption: function(element, optionName, defaultValue, skipParent) {
                optionName = optionName.indexOf('live') !== 0 ? 'live' + optionName.charAt(0).toUpperCase() + optionName.slice(1) : optionName;
                skipParent = typeof skipParent !== "undefined" ? skipParent : false;
                var option = element.data(optionName), elementParent;
                if(typeof option !== "undefined" && option !== null && !(typeof option === "string" && $.trim(option) === "")) {
                    //Handle `parent` context option
                    if(optionName === 'liveContext' && option === plugin.settings.contexts.parent) {
                        elementParent = element.parents('.yii2-live-widget[data-live-context]:first');
                        defaultValue = plugin.settings.contexts.partial;
                        option = elementParent.length ? elementParent.attr('id') : defaultValue;
                    }
                    return option;
                } else {
                    //Handle `method` option on `form` element
                    if(optionName === "liveMethod" && element[0].tagName.toLowerCase() === "form" && element.attr('method')) {
                        return element.attr('method').toLowerCase();
                    }
                }
                //Get option value from parent
                if(!skipParent) {
                    elementParent = element.parents('.yii2-live-widget[data-live-context]:first');
                    return elementParent.length ? plugin.utils.getElementOption(elementParent, optionName, defaultValue, true) : defaultValue;
                }
                return defaultValue;
            },
            //Get element options
            getElementOptions: function (element) {
                var options, optionValue, i;
                options = {
                    context: plugin.settings.contexts.page,
                    pushState: true,
                    method: 'get',
                    replaceAnimation: true
                };
                if(typeof element === "undefined" || !element.length) return options;
                for (i in options) {
                    optionValue = plugin.utils.getElementOption(element, i, options[i]);
                    options[i] = optionValue;
                }
                //disable pushState for POST requests
                if(options.method === 'post') options.pushState = false;
                return options;
            },
            //Add location data fields to form
            formLocationDataAdd: function (form) {
                var data = {}, field;
                data.url = window.location.href;
                data.query = window.location.search;
                for (var i in data) {
                    field = jQuery('<input type="hidden" class="location-data-field" name="_locationData['+i+']" value="">');
                    field.val(data[i]);
                    form.append(field);
                }
                return data;
            },
            //Remove location data fields from form
            formLocationDataRemove: function (form) {
                form.find('.location-data-field').remove();
            },
            //Reload current page
            pageReload: function (time) {
                if(typeof time !== "undefined" && time) {
                    setTimeout(function () { window.location.reload(); }, parseInt(time));
                } else {
                    window.location.reload();
                }
            },
            //Redirect user to page
            pageRedirect: function (url, time) {
                if(typeof time !== "undefined" && time) {
                    setTimeout(function () { window.location.href = url; }, parseInt(time));
                } else {
                    window.location.href = url;
                }
            }
        }
    };

    //Callbacks
    ylCallbacks = function (plugin) {
        return {
            linkClick: function (e) {
                var link = $(this), href = link.attr('href');
                plugin.request.ajax(href, {}, link);
                return false;
            },
            formSubmit: function (e) {
                var form = $(this), url = form.attr('action'), method = form.attr('method') || 'post', data, formData;
                //Store location only for POST requests
                if(method.toLowerCase() === 'post') {
                    plugin.utils.formLocationDataAdd(form);
                }
                formData = method.toLowerCase() === "post" ? new FormData(form[0]) : form.serialize();
                plugin.utils.formLocationDataRemove(form);
                data = {
                    method: method,
                    data: formData
                };
                plugin.request.ajax(url, data, form);
            },
            formFieldChange: function (e) {
                var field = $(this), form = field.parents('form:first'),
                    method = form.attr('method') || 'post',
                    url = form.attr('action') || window.location.href,
                    elementMethod = plugin.utils.getElementOption(field, 'method', method),
                    data, formData;
                if(elementMethod.toLowerCase() === 'post') {
                    plugin.utils.formLocationDataAdd(form);
                }
                formData = elementMethod.toLowerCase() === "post" ? new FormData(form[0]) : form.serialize();
                plugin.utils.formLocationDataRemove(form);
                data = {
                    data: formData
                };
                plugin.request.ajax(url, data, field);
            },
            popStateChange: function (e) {
                if(typeof e.state === "undefined" || e.state === null) {
                    window.location.reload(); return;
                }
            },
            ajaxSuccess: function (response, statusText, xhr) {
                plugin.response.processData(response);
                var element = plugin.utils.getElementActive(), pushUrl = window.location.href, elementOptions;
                if(typeof element !== "undefined" && element.length) {
                    elementOptions = plugin.utils.getElementOptions(element);
                    if(typeof elementOptions.pushState !== "undefined" && elementOptions.pushState) {
                        plugin.request.pushState(null, null, pushUrl, true);
                    }
                }
                console.log('ajaxSuccess');
            },
            ajaxError: function (xhr, statusText) {
                if(xhr.statusText === "abort") return;
                var element = plugin.utils.getElementActive(), elementOptions = plugin.utils.getElementOptions(element);
                if(elementOptions.pushState) {
                    window.location.reload();
                }
                console.error('ajaxError', xhr, xhr.status);
            },
            ajaxComplete: function (xhr, statusText) {
                plugin.loader.deActivate();
                plugin.utils.setElementActive();
                //console.log('ajaxComplete');
            },
            ajaxBeforeSend: function (xhr, settings) {
                var element = plugin.utils.getElementActive(), elementOptions = plugin.utils.getElementOptions(element);
                if(elementOptions.pushState && typeof settings.url !== "undefined") {
                    plugin.request.pushState(null, null, settings.url);
                }
            }
        };
    };

    //Dom manipulations
    ylDom = function(plugin) {
        return {
            //$.fn.replaceWith
            replaceWith: function (element, replacement, wrap) {
                var wrapElement = wrap ? element : false;
                return plugin.dom.insert(element, replacement, 'replaceWith', wrapElement);
            },
            //$.fn.html
            html: function (element, html, wrap) {
                var wrapElement = wrap ? element : false;
                return plugin.dom.insert(element, html, 'html', wrapElement);
            },
            //$.fn.before
            before: function (element, html, wrap) {
                var wrapElement = wrap ? element.parent() : false;
                return plugin.dom.insert(element, html, 'before', wrapElement);
            },
            //$.fn.after
            after: function (element, html, wrap) {
                var wrapElement = wrap ? element.parent() : false;
                return plugin.dom.insert(element, html, 'after', wrapElement);
            },
            //html or element insertion
            insert: function (element, html, method, wrapElement) {
                var wrap = true, isReplace = false;
                if(typeof element === "string") element = $(element);
                if(!element.length || typeof $.fn[method] !== "function") return;
                if(typeof wrapElement === "undefined" || !wrapElement) wrap = false;
                else if(typeof wrapElement !== "object") wrapElement = element;
                //wrap element
                if(wrap) { plugin.dom.wrap(wrapElement); }
                //Get new wrapElement for replace method
                if(method === "replaceWith") { isReplace = true; }
                if(isReplace) {
                    html = $(html);
                    wrapElement = html;
                }
                $.fn[method].apply(element, [html]);
                //unwrap element
                if(wrap) {
                    setTimeout(function () { plugin.dom.unwrap(wrapElement); }, 5);
                    return wrapElement.parent();
                }
                return isReplace ? wrapElement : element;
            },
            //Wrap element
            wrap: function (element) {
                var elementAnimated = !!plugin.utils.getElementOption(element, 'replaceAnimation', plugin.settings.enableReplaceAnimation);
                var animateClass = elementAnimated ? ' animated' : '';
                var wrapperHtml = '<div class="'+plugin.settings.wrapElementClass+animateClass+' active"></div>', wrapper,
                    elMarginTop = parseInt(element.css('margin-top')), elMarginBottom = parseInt(element.css('margin-bottom')),
                    wrapperCss = {minHeight: element.outerHeight(), maxHeight: element.outerHeight()};
                //If element or any of parents is wrapped than exit
                if (element.parents('.' + plugin.settings.wrapElementClass).length) return;
                element.wrap(wrapperHtml);
                wrapper = element.parent();
                if(!isNaN(elMarginTop) && elMarginTop) wrapperCss.marginTop = elMarginTop + 'px';
                if(!isNaN(elMarginBottom) && elMarginBottom) wrapperCss.marginBottom = elMarginBottom + 'px';
                wrapper.css(wrapperCss);
            },
            //Unwrap element
            unwrap: function (element, time) {
                time = typeof time !== "undefined" ? time : 800;
                var elementAnimated = !!plugin.utils.getElementOption(element, 'replaceAnimation', plugin.settings.enableReplaceAnimation);
                var wrapper = element.parent().filter('.' + plugin.settings.wrapElementClass),
                    wH, wSh, elCss = {},
                    wMarginTop = parseInt(element.css('margin-top')), wMarginBottom = parseInt(element.css('margin-bottom'));
                if(!wrapper.length) return;
                wH = wrapper.outerHeight();
                wSh = wrapper[0].scrollHeight;
                if(wSh > wH) {
                    wrapper.addClass('max-height-exceeded');
                    wrapper.css({maxHeight: wSh});
                }
                wrapper.removeClass('active');
                var unwrapCallback = function () {
                    wrapper.removeAttr('style');
                    if(!isNaN(wMarginTop) && wMarginTop) elCss.marginTop = wMarginTop;
                    if(!isNaN(wMarginBottom) && wMarginBottom) elCss.marginBottom = wMarginBottom;
                    element.css(elCss);
                    element.unwrap('.' + plugin.settings.wrapElementClass);
                };
                if(elementAnimated) {
                    setTimeout(unwrapCallback, time);
                } else {
                    unwrapCallback();
                }
            }
        };
    };

    //Page meta data
    ylPageMeta = function (plugin) {
        return {
            process: function (data) {
                var i, metaRow, callbackName, callbackArgs;
                for(i in data) {
                    metaRow = data[i];
                    if(typeof metaRow.callback === "undefined" || typeof metaRow.args === "undefined") continue;
                    callbackName = metaRow.callback;
                    callbackArgs = metaRow.args;
                    if(typeof plugin.pageMeta[callbackName] === "function") {
                        result = plugin.pageMeta[callbackName].apply(plugin, callbackArgs);
                    }
                }
            },
            processCsrf: function (param, token) {
                var region, regionSelector = 'head', params = {tagName: 'meta', attribute: 'name', forceReplace: true},
                    items = {
                        "csrf-param": '<meta name="csrf-param" content="' + param + '">',
                        "csrf-token": '<meta name="csrf-token" content="' + token + '">'
                    };
                $('form input[name="'+param+'"]').val(token);
                region = plugin.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                plugin.pageMeta.processItems(region, items, undefined, params);
            },
            processJs: function (data) {
                var region, regionSelector, regionName, params = {tagName: 'script', attribute: 'src', order: data.order};
                regionName = typeof data.region !== "undefined" && $.trim(data.region) !== "" ? data.region : undefined;
                regionSelector = typeof data.regionSelector !== "undefined" && $.trim(data.regionSelector) !== "" ? data.regionSelector : undefined;
                if(typeof regionName !== "undefined" || typeof regionSelector !== "undefined") {
                    region = plugin.pageMeta.getRegion(regionName, regionSelector);
                    if(!region.length) return;
                    plugin.pageMeta.processItems(region, data.items, data.inline, params);
                }
            },
            processCss: function (data) {
                var region, regionSelector = 'head', inlineItems,
                    params = {tagName: 'link', attribute: 'href', order: data.order};
                inlineItems = typeof data.inline !== "undefined" ? data.inline : undefined;
                region = plugin.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                plugin.pageMeta.processItems(region, data.items, inlineItems, params);
            },
            processMeta: function (data) {
                var region, regionSelector = 'head',
                    params = {tagName: 'meta', attribute: 'name'};
                region = plugin.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                plugin.pageMeta.processItems(region, data.items, undefined, params);
            },
            processTitle: function (titleHtml) {
                var html = $('html'), title = html.find('head title'), h1 = html.find('h1');
                if(h1.length) h1.html(titleHtml);
                title.text(titleHtml);
            },
            processItems: function (region, items, inline, params) {
                var defaultParams = {
                    tagName: 'script',
                    attribute: 'src',
                    order: [],
                    forceReplace: false
                };
                if(typeof params === "undefined") params = {};
                params = $.extend({}, defaultParams, params);
                var prevItem, firstItem, i, itemKey, item, itemAttr, itemExist, inlineItems, inlineInsertCallback, inlineAfterBlocks = false;
                if(typeof items === "object" && Object.keys(items).length) {
                    params.order = typeof params.order !== "undefined" && params.order.length ? params.order : Object.keys(items);
                    for (i in params.order) {
                        itemKey = params.order[i];
                        if(typeof items[itemKey] === "undefined") continue;
                        item = $(items[itemKey]);
                        itemAttr = item.attr(params.attribute);
                        itemExist = region.find(params.tagName + '['+params.attribute+'="'+itemAttr+'"]');
                        if(!itemExist.length) {
                            if(typeof prevItem !== "undefined" && prevItem.length) {
                                prevItem.after(item);
                            } else {
                                firstItem = region.find(params.tagName + '['+params.attribute+']');
                                if(firstItem.length) {
                                    firstItem.before(item);
                                } else {
                                    region.append(item);
                                }
                            }
                        } else if(params.forceReplace) {
                            itemExist.replaceWith(item);
                        }
                        prevItem = itemExist.length ? itemExist : item;
                    }
                }
                if(typeof inline !== "undefined" && $.trim(inline) !== "") {
                    switch (params.tagName) {
                        case 'script':
                            inlineAfterBlocks = true;
                            inlineItems = region.find(params.tagName + ':not([src])');
                            inlineItems.remove();
                            inlineInsertCallback = function () {
                                region.append(inline);
                            };
                            break;
                        case 'link':
                            inlineItems = region.find('style');
                            region.append(inline);
                            inlineItems.remove();
                            break;
                    }
                    //Inline scripts must be inserted after widgets insertion
                    if(inlineAfterBlocks && typeof inlineInsertCallback === "function") {
                        var inlineInsertCallbackWrap = function (e) {
                            if(typeof inlineInsertCallback === "function") inlineInsertCallback(e);
                            $(document).off(plugin.events.EVENT_WIDGETS_LOADED, inlineInsertCallbackWrap);
                        };
                        $(document).on(plugin.events.EVENT_WIDGETS_LOADED, inlineInsertCallbackWrap);
                    }
                }
            },
            getRegion: function (name, selector) {
                if(typeof name !== "undefined") {
                    return $('[data-live-region="'+name+'"]');
                }
                if(typeof selector !== "undefined") {
                    return $(selector);
                }
            }
        };
    };

    //Page widgets
    ylPageWidgets = function (plugin) {
        return {
            process: function (data) {
                var i, widgetRow, callbackName, callbackArgs;
                for(i in data) {
                    widgetRow = data[i];
                    if(typeof widgetRow.callback === "undefined") continue;
                    callbackName = widgetRow.callback;
                    if(typeof plugin.pageWidgets[callbackName] === "function") {
                        plugin.pageWidgets[callbackName].apply(plugin, [widgetRow]);
                    }
                }
                $(document).trigger(plugin.events.EVENT_WIDGETS_LOADED);
            },
            processHtml: function (data) {
                var widget = $('#' + data.id);
                var insertMethod = typeof data.insertMethod !== "undefined" ? data.insertMethod : 'insert';
                if(typeof data.dataHtml === "undefined" || !widget.length) return;
                if(insertMethod === "replace") {
                    widget = plugin.dom.replaceWith(widget, data.dataHtml, true);
                } else {
                    plugin.dom.html(widget, data.dataHtml, true);
                }
                widget.trigger(plugin.events.EVENT_HTML_INSERT);
            },
            processConfigurable: function (data) {

            },
            processCombined: function (data) {

            }
        };
    };

    //Loading animation
    ylLoader = function (plugin) {
        return {
            activateTime: 0,
            getElem: function () {
                return $('.yii2-live-loading-indicator:first');
            },
            activate: function () {
                var elem = plugin.loader.getElem();
                elem.addClass('active');
                this.activateTime = Date.now();
                plugin.loader.clearProgressMessages();
            },
            deActivate: function (force) {
                force = typeof force !== "undefined" ? !!force : false;
                var elem = plugin.loader.getElem(), activateMaxTime = Date.now() - 500;
                if(this.activateTime > activateMaxTime && !force) {
                    setTimeout(function () {
                        elem.removeClass('active');
                    }, this.activateTime - activateMaxTime);
                } else {
                    elem.removeClass('active');
                }
            },
            getElemProgressMessages: function () {
                var elem = plugin.loader.getElem();
                return elem.length ? elem.find('.progress-messages-area') : [];
            },
            getElemProgressMessage: function (key) {
                if(typeof key === "object" && typeof key.length !== "undefined") return key;
                var wrap = plugin.loader.getElemProgressMessages();
                return wrap.length ? wrap.find('[data-key="'+key+'"]') : [];
            },
            addProgressMessage: function (message, key, finished) {
                var messageElem = $('<div class="progress-message">'+message+'</div>');
                finished = typeof finished !== "undefined" ? !!finished : false;
                messageElem.attr('data-key', key);
                var wrap = plugin.loader.getElemProgressMessages(), messageElemExistent = plugin.loader.getElemProgressMessage(key);
                if(messageElemExistent.length) messageElemExistent.remove();
                if(wrap.length) wrap.append(messageElem);
                messageElem.data('addedTime', Date.now());
                if(finished) plugin.loader.finishProgressMessage(messageElem);
            },
            finishProgressMessage: function (key) {
                var messageElem = plugin.loader.getElemProgressMessage(key);
                if(messageElem.length) messageElem.addClass('finished');
            },
            clearProgressMessages: function () {
                var wrap = plugin.loader.getElemProgressMessages(), addedMaxTime = Date.now() - 2000;
                if(!wrap.length) return;
                wrap.find('.progress-message').each(function () {
                    var messageElem = $(this), addedTime = parseInt(messageElem.data('addedTime'));
                    addedTime = isNaN(addedTime) ? 0 : addedTime;
                    if(addedTime > addedMaxTime) {
                        setTimeout(function () { messageElem.remove(); }, addedTime - addedMaxTime);
                    } else {
                        messageElem.remove();
                    }
                });
            }
        };
    };

    //Init callback
    ylInit = function (plugin) {
        var linksSelector = plugin.settings.enableLiveLoad
            ? plugin.settings.linkSelector + ', ' + plugin.settings.linkSelectorAjax
            : plugin.settings.linkSelectorAjax,
            formSelector = plugin.settings.enableLiveLoad
                ? plugin.settings.formSelector + ', ' + plugin.settings.formSelectorAjax
                : plugin.settings.formSelectorAjax;
        if(plugin.utils.isPushStateSupported()) {
            window.addEventListener('popstate', plugin.callbacks.popStateChange);
        }
        //Links click
        $(document).on('click', linksSelector, function (e) {
            var link = $(this), confirm = link.data('liveConfirm'), href = link.attr('href');
            if(!plugin.utils.isLocalUrl(href)) return;
            if(link.attr('target') === "_blank" || link.attr('data-live') === "0" || link.attr('data-live-enabled') === "0") return;
            if(e.isDefaultPrevented()) return;
            e.preventDefault();
            if(confirm) {
                yii.confirm(confirm, function () {
                    return plugin.callbacks.linkClick.apply(link, [e]);
                }, function () {});
            } else {
                return plugin.callbacks.linkClick.apply(link, [e]);
            }
        });
        //Form submit
        $(document).on('beforeSubmit', formSelector, function (e) {
            var form = $(this), confirm = form.data('confirmMessage');
            if(confirm) {
                yii.confirm(confirm, function () {
                    return plugin.callbacks.formSubmit.apply(form, [e]);
                }, function () {});
            } else {
                return plugin.callbacks.formSubmit.apply(form, [e]);
            }
        }).on('submit', formSelector, function (e) {
            e.preventDefault();
            var form = $(this);
            if(form.hasClass('gridview-filter-form')) $(this).trigger('beforeSubmit');
            return false;
        });
        //Form field change
        $(document).on('change', plugin.settings.fieldSelectorAjax, function(e){
            var field = $(this);
            return plugin.callbacks.formFieldChange.apply(field, [e]);
        });
    };

    //Constructor
    function Yii2Live(options) {
        this.testSetting = {test: 'value'}
        this.settingsDefault = {
            headerName: "X-Yii2-Live",
            headerNameContext: "X-Yii2-Live-Context",
            enableLiveLoad: false,
            enableReplaceAnimation: false,
            requestId: "",
            linkSelector: "a",
            linkSelectorAjax: "a[data-live-context], a[data-live-enabled], [data-live-context] a",
            formSelector: "form",
            formSelectorAjax: "form[data-live-context], form[data-live-enabled], [data-live-context] form.gridview-filter-form",
            fieldSelectorAjax: ".form-control[data-live-context], .form-control[data-live-enabled], .checkbox input[data-live-enabled], .radio input[data-live-enabled], .btn input[data-live-enabled]",
            modalDefaultSelector: '#modal-general',
            wrapElementClass: 'yii2live-element-ajax-wrapper',
            messageAdapter: 'alert',
            contexts: {
                page: 'page',
                partial: 'partial',
                parent: 'parent'
            },
            domainsLocal: [
                '//' + window.location.host, window.location.protocol + '//' + window.location.host
            ]
        };
        this.events = {
            EVENT_HTML_INSERT: 'yii2live:html',
            EVENT_WIDGETS_LOADED: 'yii2live:widgetsLoaded'
        };
        this.activeElement = undefined;
        this.pushStateSupported = undefined;
        this.settings = {};

        options = typeof options !== "undefined" ? options : {};
        this.settings = $.extend({}, this.settingsDefault, options);

        this.settingsUtils = ylSettingsUtils(this);
        this.utils = ylUtils(this);
        this.callbacks = ylCallbacks(this);
        this.request = ylRequest(this);
        this.response = ylResponse(this);
        this.dom = ylDom(this);
        this.loader = ylLoader(this);
        this.pageWidgets = ylPageWidgets(this);
        this.pageMeta = ylPageMeta(this);
        this.ajaxCmd = yii2liveCmd(this);

        //Initialize
        ylInit(this);

        //public data
        return {
            settingsUtils: this.settingsUtils,
            utils: this.utils,
            request: this.request,
            response: this.response,
            dom: this.dom,
            loader: this.loader,
            pageWidgets: this.pageWidgets,
            pageMeta: this.pageMeta,
            ajaxCmd: this.ajaxCmd,
            events: $.extend({}, this.events)
        };
    }

    //Prototyping
    Yii2Live.prototype.self = function() { return this; };

    return Yii2Live;
});

//yii2Live = new Yii2Live();

if(typeof YiiNodeSockets !== "undefined" && typeof YiiNodeSockets.callbacks !== "undefined") {
    //just test callback to show how it must be written
    YiiNodeSockets.callbacks.yii2liveLoaderCallback = function (message, _socket) {
        if(typeof yii2live === "undefined") return;
        switch (message.body.type) {
            case 'addMessage':
                var finished = parseInt(message.body.messageFinished) === 1;
                yii2live.loader.addProgressMessage(message.body.message, message.body.messageKey, finished);
                break;
            case 'finishMessage':
                yii2live.loader.finishProgressMessage(message.body.messageKey);
                break;
            case 'flushMessages':
                yii2live.loader.clearProgressMessages();
                break;
        }
    };
}