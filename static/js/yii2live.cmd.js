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
            }
        },
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
        //Pjax methods
        cmdPjax: function (method, args) {
            if(typeof jQuery.pjax === "undefined" || typeof jQuery.pjax[method] !== "function") return;
            return jQuery.pjax[method].apply(null, args);
        },
        //Helper functions
        getFunctionRecursive: function (baseObject, methodChain) {
            if (typeof baseObject === "undefined") baseObject = window;
            var method, methodArray, object = baseObject, i;
            methodArray = methodChain.split('.');
            for (i in methodArray) {
                method = methodArray[i];
                if(typeof object[method] === "object" || typeof object[method] === "function") { object = object[method]; }
            }
            return typeof object === "function" ? object : undefined;
        }
    };
};