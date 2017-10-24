yii2liveCmd = function (self) {
    return {
        processCmd: function (data) {
            var selector = typeof data.selector !== "undefined" ? data.selector : undefined,
                type = typeof data.type !== "undefined" ? data.type : 'jQuery',
                method = typeof data.method !== "undefined" ? data.method : undefined,
                args = typeof data.args !== "undefined" ? data.args : [];
            switch (type) {
                case 'jQuery':
                    return this.cmdjQuery(selector, method, args);
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
            var element = $(selector);
            return jQuery.fn[method].apply(element, args);
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