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
                e.preventDefault();
                self.request.ajax(href, {method: 'get'});
            },
            popStateChange: function (e) {
                if(typeof e.state === "undefined" || e.state === null) {
                    window.location.reload(); return;
                }
                //$('html').html(e.state);
            },
            ajaxSuccess: function (response, statusText, xhr) {
                self.response.processData(response);
                self.request.pushState($('html').html(), null, window.location.pathname, true);
                console.log('ajaxSuccess');
            },
            ajaxError: function (xhr, statusText) {
                history.back();
                console.log('ajaxError', xhr, xhr.status);
            },
            ajaxComplete: function (xhr, statusText) {
                //console.log('ajaxComplete');
            }
        };
    }();

    //Request
    this.request = function () {
        var rq = this;

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
                if(typeof url === "undefined") {
                    console.error('Required parameter `url` is missing'); return;
                }
                if(self.utils.isPushStateSupported()) {
                    self.request.pushState(null, null, url);
                }
                options = $.extend({}, rq.getDefaultOptions(), options);
                $.ajax(url, options);
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
                var i, metaRow, callbackName, callbackArgs;
                if(typeof data.meta !== "undefined") {
                    for(i in data.meta) {
                        metaRow = data.meta[i];
                        if(typeof metaRow.callback === "undefined" || typeof metaRow.args === "undefined") continue;
                        callbackName = metaRow.callback;
                        callbackArgs = metaRow.args;
                        if(typeof self.response[callbackName] === "function") {
                            result = self.response[callbackName].apply(self, callbackArgs);
                        }
                    }
                }
            },
            processJs: function (data) {
                console.log(data);
            },
            processTitle: function (titleHtml) {
                var html = $('html'), title = html.find('head title'), h1 = html.find('h1');
                if(h1.length) h1.html(titleHtml);
                title.text(titleHtml);
            }
        };
    }();

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

    return Yii2Live;
});

//yii2Live = new Yii2Live();