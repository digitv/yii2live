(function (global, factory) {
    global.Yii2Live = factory();
})(this, function () {
    var self = this;
    this.settingsDefault = {
        headerName: "X-Yii2-Live",
        linkSelector: "a",
        formSelector: "form",
        domainsLocal: [
            '//' + window.location.host, window.location.protocol + '//' + window.location.host
        ]
    };
    this.events = {
        EVENT_HTML_INSERT: 'yii2live:html',
        EVENT_WIDGETS_LOADED: 'yii2live:widgetsLoaded'
    };
    this.pushStateSupported = undefined;
    this.settings = {};

    this.settingsUtils = function () {
        return {
            get: function (key) {
                if(typeof key === "string") {
                    return typeof self.settings[key] !== "undefined" ? self.settings[key] : undefined;
                }
                return self.settings;
            },
            merge: function (settingsNew) {
                if(typeof settingsNew !== "object") return;
                self.settings = $.extend({}, self.settingsDefault, self.settings, settingsNew);
            }
        };
    }();

    //Utilities
    this.utils = function () {
        return {
            isLocalUrl: function (url) {
                url = $.trim(url);
                if(url === "/" || url === "") return true;
                if(new RegExp('^\/(?!\/)').test(url)) return true;
                if(typeof self.settings.domainsLocal !== "undefined" && self.settings.domainsLocal.length) {
                    for (var i in self.settings.domainsLocal) {
                        if(url.indexOf(self.settings.domainsLocal[i]) === 0) return true;
                    }
                }
                return false;
            },
            isPushStateSupported: function () {
                if(typeof self.pushStateSupported === "undefined") {
                    self.pushStateSupported = !!(history && history.pushState);
                }
                return self.pushStateSupported;
            }
        }
    }();

    //Callbacks
    this.callbacks = function () {
        return {
            linkClick: function (e) {
                var link = $(this), href = link.attr('href');
                if(!self.utils.isLocalUrl(href)) return;
                if(link.attr('target') === "_blank" || link.attr('data-live') === "0") return;
                e.preventDefault();
                self.request.ajax(href, {method: 'get'});
            },
            popStateChange: function (e) {
                if(typeof e.state === "undefined" || e.state === null) {
                    window.location.reload(); return;
                }
            },
            ajaxSuccess: function (response, statusText, xhr) {
                self.response.processData(response);
                self.request.pushState(null, null, window.location.pathname, true);
                console.log('ajaxSuccess');
            },
            ajaxError: function (xhr, statusText) {
                if(xhr.statusText === "abort") return;
                history.back();
                console.error('ajaxError', xhr, xhr.status);
            },
            ajaxComplete: function (xhr, statusText) {
                self.loader.deActivate();
                //console.log('ajaxComplete');
            }
        };
    }();

    //Request
    this.request = function () {
        var rq = this;

        this.ajaxRequest = null;

        this.defaultOptions = {
            method: 'post',
            headers: {},
            success: self.callbacks.ajaxSuccess,
            error: self.callbacks.ajaxError,
            complete: self.callbacks.ajaxComplete
        };
        this.getDefaultOptions = function () {
            var options = rq.defaultOptions;
            options.headers[self.settings.headerName] = 'true';
            return options;
        };

        return {
            ajax: function (url, options) {
                self.request.ajaxAbort();
                self.loader.activate();
                if(typeof url === "undefined") {
                    console.error('Required parameter `url` is missing'); return;
                }
                if(self.utils.isPushStateSupported()) {
                    self.request.pushState(null, null, url);
                }
                options = $.extend({}, rq.getDefaultOptions(), options);
                rq.ajaxRequest = $.ajax(url, options);
            },
            ajaxAbort: function () {
                if(rq.ajaxRequest) {
                    rq.ajaxRequest.abort();
                    rq.ajaxRequest = null;
                }
                self.loader.deActivate();
            },
            pushState: function (data, title, url, replace) {
                if(!self.utils.isPushStateSupported()) return;
                replace = typeof replace === "undefined" ? false : replace;
                if(window.location.pathname === url) replace = true;
                if(replace)
                    return history.replaceState(data, title, url);
                else
                    return history.pushState(data, title, url);
            }
        };
    }();

    //Response
    this.response = function () {
        return {
            processData: function (data) {
                console.log(data);
                if(typeof data.meta !== "undefined") {
                    self.pageMeta.process(data.meta);
                }
                if(typeof data.widgets !== "undefined") {
                    self.pageWidgets.process(data.widgets);
                }
            }
        };
    }();

    //Response
    this.loader = function () {
        return {
            getElem: function () {
                return $('.yii2-live-loading-indicator:first');
            },
            activate: function () {
                var elem = self.loader.getElem();
                elem.addClass('active');
            },
            deActivate: function () {
                var elem = self.loader.getElem();
                elem.removeClass('active');
            }
        };
    }();

    this.pageMeta = function () {
        return {
            process: function (data) {
                var i, metaRow, callbackName, callbackArgs;
                for(i in data) {
                    metaRow = data[i];
                    if(typeof metaRow.callback === "undefined" || typeof metaRow.args === "undefined") continue;
                    callbackName = metaRow.callback;
                    callbackArgs = metaRow.args;
                    if(typeof self.pageMeta[callbackName] === "function") {
                        result = self.pageMeta[callbackName].apply(self, callbackArgs);
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
                region = self.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                self.pageMeta.processItems(region, items, undefined, params);
            },
            processJs: function (data) {
                var region, regionSelector, regionName, params = {tagName: 'script', attribute: 'src', order: data.order};
                regionName = typeof data.region !== "undefined" && $.trim(data.region) !== "" ? data.region : undefined;
                regionSelector = typeof data.regionSelector !== "undefined" && $.trim(data.regionSelector) !== "" ? data.regionSelector : undefined;
                if(typeof regionName !== "undefined" || typeof regionSelector !== "undefined") {
                    region = self.pageMeta.getRegion(regionName, regionSelector);
                    if(!region.length) return;
                    self.pageMeta.processItems(region, data.items, data.inline, params);
                }
            },
            processCss: function (data) {
                var region, regionSelector = 'head', inlineItems,
                    params = {tagName: 'link', attribute: 'href', order: data.order};
                inlineItems = typeof data.inline !== "undefined" ? data.inline : undefined;
                region = self.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                self.pageMeta.processItems(region, data.items, inlineItems, params);
            },
            processMeta: function (data) {
                var region, regionSelector = 'head',
                    params = {tagName: 'meta', attribute: 'name'};
                region = self.pageMeta.getRegion(undefined, regionSelector);
                if(!region.length) return;
                self.pageMeta.processItems(region, data.items, undefined, params);
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
                            $(document).off(self.events.EVENT_WIDGETS_LOADED, inlineInsertCallbackWrap);
                        };
                        $(document).on(self.events.EVENT_WIDGETS_LOADED, inlineInsertCallbackWrap);
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
    }();

    this.pageWidgets = function () {
        return {
            process: function (data) {
                var i, widgetRow, callbackName, callbackArgs;
                for(i in data) {
                    widgetRow = data[i];
                    if(typeof widgetRow.callback === "undefined") continue;
                    callbackName = widgetRow.callback;
                    if(typeof self.pageWidgets[callbackName] === "function") {
                        result = self.pageWidgets[callbackName].apply(self, [widgetRow]);
                    }
                }
                $(document).trigger(self.events.EVENT_WIDGETS_LOADED);
            },
            processHtml: function (data) {
                var widget = $('#' + data.id);
                var insertMethod = typeof data.insertMethod !== "undefined" ? data.insertMethod : 'insert';
                if(typeof data.dataHtml === "undefined" || !widget.length) return;
                if(insertMethod === "replace") {
                    widget.replaceWith(data.dataHtml);
                } else {
                    widget.html(data.dataHtml);
                }
                widget.trigger(self.events.EVENT_HTML_INSERT);
            }
        };
    }();

    this.ajaxCmd = yii2liveCmd(self);

    //Init callback
    this.init = function () {
        if(self.utils.isPushStateSupported()) {
            window.addEventListener('popstate', self.callbacks.popStateChange);
        }
        $(document).on('click', self.settings.linkSelector, self.callbacks.linkClick);
    };

    //Constructor
    function Yii2Live(options) {
        options = typeof options !== "undefined" ? options : {};
        self.settings = $.extend({}, self.settingsDefault, options);
        self.init();
    }

    //Prototyping
    Yii2Live.prototype.utils = this.utils;
    Yii2Live.prototype.response = this.response;
    Yii2Live.prototype.settings = this.settingsUtils;
    Yii2Live.prototype.ajaxCmd = this.ajaxCmd;

    return Yii2Live;
});

//yii2Live = new Yii2Live();