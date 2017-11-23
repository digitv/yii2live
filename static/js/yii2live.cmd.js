yii2liveCmd = function (self) {
    return {
        process: function (commands) {
            var command, i;
            for (i in commands) {
                command = commands[i];
                this.processCmd(command);
            }
        },
        processCmd: function (data) {
            var selector = typeof data.selector !== "undefined" ? data.selector : undefined,
                type = typeof data.type !== "undefined" ? data.type : 'jQuery',
                method = typeof data.method !== "undefined" ? data.method : undefined,
                args = typeof data.args !== "undefined" ? data.args : [],
                i, command, context;
            switch (type) {
                case 'jQuery':
                    return this.cmdjQuery(selector, method, args);
                    break;
                case 'jQueryChain':
                    for (i in data.commands) {
                        command = data.commands[i];
                        if(typeof context !== "undefined") command.selector = context;
                        context = this.processCmd(command);
                    }
                    return this.cmdjQuery(selector, method, args);
                    break;
                case 'pjax':
                    return this.cmdPjax(method, args);
                    break;
                case 'live':
                    return this.cmdLive(method, args);
                    break;
                case 'modal':
                    return this.cmdModal(selector, method, args);
                    break;
                case 'message':
                    return this.cmdMessage(method, args);
                    break;
            }
        },
        /** Different commands */
        //Yii2Live commands
        cmdLive: function (method, args) {
            var methodFunction = this.getFunctionRecursive(self, method);
            if(typeof methodFunction === "function") {
                return methodFunction.apply(self, args);
            }
        },
        //jQuery methods
        cmdjQuery: function (selector, method, args) {
            if(typeof jQuery.fn[method] !== "function") return;
            var element = typeof selector === "object" ? selector : $(selector);
            return jQuery.fn[method].apply(element, args);
        },
        //Bootstrap modal methods
        cmdModal: function (selector, method, args) {
            var element = this.getModal(selector);
            if(!element.length || typeof jQuery.fn.modal !== "function") return [];
            if(method === "body") {
                if(!element.is(':visible')) element.modal('show');
                return jQuery.fn.html.apply(element.find('.modal-body'), args);
            } else if(method === "title") {
                if(!element.is(':visible')) element.modal('show');
                return jQuery.fn.html.apply(element.find('.modal-title'), args);
            } else {
                args.unshift(method);
                return jQuery.fn.modal.apply(element, args);
            }
        },
        //Pjax methods
        cmdPjax: function (method, args) {
            if(typeof jQuery.pjax === "undefined" || typeof jQuery.pjax[method] !== "function") return;
            return jQuery.pjax[method].apply(null, args);
        },
        //Message methods
        cmdMessage: function (method, args) {
            if(typeof self.settings.messageAdapter === "undefined" || typeof this.messageAdapters[self.settings.messageAdapter] === "undefined") return;
            this.messageAdapters[self.settings.messageAdapter].show.apply(this.messageAdapters[self.settings.messageAdapter], args);
        },
        /** Helper functions */
        //Get function from object recursive
        getFunctionRecursive: function (baseObject, methodChain) {
            if (typeof baseObject === "undefined") baseObject = window;
            var method, methodArray, object = baseObject, i;
            methodArray = methodChain.split('.');
            for (i in methodArray) {
                method = methodArray[i];
                if(typeof object[method] === "object" || typeof object[method] === "function") { object = object[method]; }
            }
            return typeof object === "function" ? object : undefined;
        },
        //Get bootstrap modal (by selector|visible|default)
        getModal: function (selector) {
            var visibleModal;
            if(typeof selector === "undefined" || selector === null) {
                visibleModal = $('.modal:visible:first');
                return visibleModal.length ? visibleModal : jQuery(self.settings.modalSelector);
            }
            return jQuery(selector);
        },
        //different message adapters
        messageAdapters: {
            //JS simple alert
            alert: {
                settings: {},
                show: function (message, type) { alert(message); }
            },
            //$.notify
            notify: {
                settings: {
                    showProgressbar: true,
                    template: '<div class="col-xs-11 col-sm-3 alert alert-{0}" role="alert" data-notify="container"><button type="button" class="close" data-notify="dismiss"><span aria-hidden="true">&times;</span></button><span data-notify="icon"></span><span data-notify="title">{1}</span><span data-notify="message">{2}</span><div class="progress kv-progress-bar" data-notify="progressbar"><div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div></div><a href="{3}" data-notify="url" target="{4}"></a></div>',
                },
                icons: {
                    success: 'fa fa-check-circle',
                    info: 'fa fa-info-circle',
                    danger: 'fa fa-exclamation-triangle',
                    warning: 'fa fa-exclamation-triangle'
                },
                show: function (message, type) {
                    if(typeof jQuery.notify === "undefined") return;
                    var iconClass, settings, newSettings;
                    type = typeof type !== "undefined" ? type : 'info';
                    iconClass = this.getIconClass(type);
                    newSettings = {type: type};
                    if(typeof iconClass !== "undefined") newSettings.content = {icon: iconClass + ' margin-right-5'};
                    settings = $.extend({}, this.settings, newSettings);
                    jQuery.notify({message: message}, settings);
                },
                getIconClass: function (type) {
                    return typeof this.icons[type] !== "undefined" ? this.icons[type] : undefined;
                }
            }
        }
    };
};