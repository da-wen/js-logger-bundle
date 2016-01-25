(function (window, old) {
    var self = {},
        lastEvent,
        lastScript,
        previousNotification,
        shouldCatch = true,
        ignoreOnError = 0,
        eventsRemaining = 10,
        maxPayloadDepth = 5;

    self.noConflict = function() {
        window.JsLogger = old;
        if (typeof old === "undefined") {
            delete window.JsLogger;
        }
        return self;
    };

    // Resets the rate limit
    self.refresh = function() {
        eventsRemaining = 10;
    };

    //
    // ### Manual error notification
    //
    self.notifyException = function (exception, name, metaData, level) {
        if (!exception) {
            return;
        }
        if (name && typeof name !== "string") {
            metaData = name;
            name = undefined;
        }
        if (!metaData) {
            metaData = {};
        }
        addScriptToMetaData(metaData);

        sendToBackend({
            name: name || exception.name,
            message: exception.message || exception.description,
            stacktrace: stacktraceFromException(exception) || generateStacktrace(),
            file: exception.fileName || exception.sourceURL,
            lineNumber: exception.lineNumber || exception.line,
            columnNumber: exception.columnNumber ? exception.columnNumber + 1 : undefined,
            level: level || "warning"
        }, metaData);
    };

    self.notify = function (name, message, metaData, level) {
        sendToBackend({
            name: name,
            message: message,
            stacktrace: generateStacktrace(),
            file: window.location.toString(),
            lineNumber: 1,
            level: level || "warning"
        }, metaData);
    };

    function wrap(_super, options) {
        try {
            if (typeof _super !== "function") {
                return _super;
            }
            if (!_super.jslogger) {
                var currentScript = getCurrentScript();
                _super.jslogger = function (event) {
                    if (options && options.eventHandler) {
                        lastEvent = event;
                    }
                    lastScript = currentScript;

                    // We set shouldCatch to false on IE < 10 because catching the error ruins the file/line as reported in window.onerror,
                    // We set shouldCatch to false on Chrome/Safari because it interferes with "break on unhandled exception"
                    // All other browsers need shouldCatch to be true, as they don't pass the exception object to window.onerror
                    if (shouldCatch) {
                        try {
                            return _super.apply(this, arguments);
                        } catch (e) {
                            // We do this rather than stashing treating the error like lastEvent
                            // because in FF 26 onerror is not called for synthesized event handlers.
                            if (getSetting("autoNotify", true)) {
                                self.notifyException(e, null, null, "error");
                                ignoreNextOnError();
                            }
                            throw e;
                        } finally {
                            lastScript = null;
                        }
                    } else {
                        var ret = _super.apply(this, arguments);
                        // in case of error, this is set to null in window.onerror
                        lastScript = null;
                        return ret;
                    }
                };
                _super.jslogger.jslogger = _super.jslogger;
            }

            return _super.jslogger;

        } catch (e) {
            return _super;
        }
    }

    //
    // ### Script tag tracking
    //

    // To emulate document.currentScript we use document.scripts.last.
    var synchronousScriptsRunning = document.readyState !== "complete";
    function loadCompleted() {
        synchronousScriptsRunning = false;
    }

    if (document.addEventListener) {
        document.addEventListener("DOMContentLoaded", loadCompleted, true);
        window.addEventListener("load", loadCompleted, true);
    } else {
        window.attachEvent("onload", loadCompleted);
    }

    function getCurrentScript() {
        var script = document.currentScript || lastScript;

        if (!script && synchronousScriptsRunning) {
            var scripts = document.scripts || document.getElementsByTagName("script");
            script = scripts[scripts.length - 1];
        }

        return script;
    }

    function addScriptToMetaData(metaData) {
        var script = getCurrentScript();

        if (script) {
            metaData.script = {
                src: script.src,
                content: getSetting("inlineScript", true) ? script.innerHTML : ""
            };
        }
    }

    //
    // ### Helpers & Setup
    //

    // Compile regular expressions upfront.
    var FUNCTION_REGEX = /function\s*([\w\-$]+)?\s*\(/i;

    // Set up default notifier settings.
    var DEFAULT_BASE_ENDPOINT = "/jslogger/log";

    // Keep a reference to the currently executing script in the DOM.
    // We'll use this later to extract settings from attributes.
    var scripts = document.getElementsByTagName("script");
    var thisScript = scripts[scripts.length - 1];
    var allowedLevels = getSetting('allowed-levels', '');
    allowedLevels = (0 === allowedLevels.length) ? [] : allowedLevels.split(',');

    // Simple logging function that wraps `console.log` if available.
    function log(msg) {
        var disableLog = getSetting("disableLog");

        var console = window.console;
        if (console !== undefined && console.log !== undefined && !disableLog) {
            console.log("[JsLogger] " + msg);
        }
    }

    // Deeply serialize an object into a query string. We use the PHP-style
    // nested object syntax, `nested[keys]=val`, to support heirachical
    // objects.
    function serialize(obj, prefix, depth) {
        var maxDepth = getSetting("maxDepth", maxPayloadDepth);

        if (depth >= maxDepth) {
            return encodeURIComponent(prefix) + "=[RECURSIVE]";
        }
        depth = depth + 1 || 1;

        try {
            if (window.Node && obj instanceof window.Node) {
                return encodeURIComponent(prefix) + "=" + encodeURIComponent(targetToString(obj));
            }

            var str = [];
            for (var p in obj) {
                if (obj.hasOwnProperty(p) && p != null && obj[p] != null) {
                    var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
                    str.push(typeof v === "object" ? serialize(v, k, depth) : encodeURIComponent(k) + "=" + encodeURIComponent(v));
                }
            }
            return str.join("&");
        } catch (e) {
            return encodeURIComponent(prefix) + "=" + encodeURIComponent("" + e);
        }
    }

    // Deep-merge the `source` object into the `target` object and return
    // the `target`.
    function merge(target, source, depth) {
        if (source == null) {
            return target;
        } else if (depth >= getSetting("maxDepth", maxPayloadDepth)) {
            return "[RECURSIVE]";
        }

        target = target || {};
        for (var key in source) {
            if (source.hasOwnProperty(key)) {
                try {
                    if (source[key].constructor === Object) {
                        target[key] = merge(target[key], source[key], depth + 1 || 1);
                    } else {
                        target[key] = source[key];
                    }
                } catch (e) {
                    target[key] = source[key];
                }
            }
        }

        return target;
    }

    // Make a HTTP request with given `url` and `params` object.
    function request(url, params) {
        url += "?" + serialize(params) + "&ct=img&cb=" + new Date().getTime();
        var img = new Image();
        img.src = url;
    }

    // Extract all `data-*` attributes from a DOM element and return them as an
    // object.
    function getData(node) {
        var dataAttrs = {};
        var dataRegex = /^data\-([\w\-]+)$/;

        // If the node doesn't exist due to being loaded as a commonjs module,
        // then return an empty object and fallback to self[].
        if (node) {
            var attrs = node.attributes;
            for (var i = 0; i < attrs.length; i++) {
                var attr = attrs[i];
                if (dataRegex.test(attr.nodeName)) {
                    var key = attr.nodeName.match(dataRegex)[1];
                    dataAttrs[key] = attr.value || attr.nodeValue;
                }
            }
        }

        return dataAttrs;
    }


    var data;
    function getSetting(name, fallback) {
        data = data || getData(thisScript);
        var setting = self[name] !== undefined ? self[name] : data[name.toLowerCase()];
        if (setting === "false") {
            setting = false;
        }
        return setting !== undefined ? setting : fallback;
    }

    // Send an error to backend.
    function sendToBackend(details, metaData) {
        if(0 !== allowedLevels.length && -1 == allowedLevels.indexOf(details.level)) {
            return;
        }

        eventsRemaining -= 1;

        // Don't send multiple copies of the same error.
        var deduplicate = [details.name, details.message, details.stacktrace].join("|");
        if (deduplicate === previousNotification) {
            return;
        } else {
            previousNotification = deduplicate;
        }

        if (lastEvent) {
            metaData = metaData || {};
            metaData["Last Event"] = eventToMetaData(lastEvent);
        }

        var payload = {
            projectRoot: getSetting("projectRoot") || window.location.protocol + "//" + window.location.host,
            context: getSetting("context") || window.location.pathname,
            userId: getSetting("userId"), // Deprecated, remove in v3
            user: getSetting("user"),
            metaData: merge(merge({}, getSetting("metaData")), metaData),
            appVersion: getSetting("appVersion"),

            url: window.location.href,
            userAgent: navigator.userAgent,
            language: navigator.language || navigator.userLanguage,

            level: details.level,

            name: details.name,
            message: details.message,
            stacktrace: details.stacktrace,
            file: details.file,
            lineNumber: details.lineNumber,
            columnNumber: details.columnNumber,
            payloadVersion: "1"
        };

        // Run any beforeNotify function
        var beforeNotify = self.beforeNotify;
        if (typeof(beforeNotify) === "function") {
            var retVal = beforeNotify(payload, payload.metaData);
            if (retVal === false) {
                return;
            }
        }

        if (payload.lineNumber === 0 && (/Script error\.?/).test(payload.message)) {
            return log("Ignoring cross-domain script error.");
        }

        // Make the HTTP request
        request(getSetting("endpoint") || getSetting('backend-url', DEFAULT_BASE_ENDPOINT), payload);
    }

    // Generate a browser stacktrace from the current stack.
    function generateStacktrace() {
        var generated, stacktrace;
        var MAX_FAKE_STACK_SIZE = 10;
        var ANONYMOUS_FUNCTION_PLACEHOLDER = "[anonymous]";

        // Try to generate a real stacktrace (most browsers, except IE9 and below).
        try {
            throw new Error("");
        } catch (exception) {
            generated = "<generated>\n";
            stacktrace = stacktraceFromException(exception);
        }

        // Otherwise, build a fake stacktrace.
        if (!stacktrace) {
            generated = "<generated-ie>\n";
            var functionStack = [];
            try {
                var curr = arguments.callee.caller.caller;
                while (curr && functionStack.length < MAX_FAKE_STACK_SIZE) {
                    var fn = FUNCTION_REGEX.test(curr.toString()) ? RegExp.$1 || ANONYMOUS_FUNCTION_PLACEHOLDER : ANONYMOUS_FUNCTION_PLACEHOLDER;
                    functionStack.push(fn);
                    curr = curr.caller;
                }
            } catch (e) {
                log(e);
            }
            stacktrace = functionStack.join("\n");
        }

        return generated + stacktrace;
    }

    // Get the stacktrace string from an exception
    function stacktraceFromException(exception) {
        return exception.stack || exception.backtrace || exception.stacktrace;
    }

    // Populate the event tab of meta-data.
    function eventToMetaData(event) {
        return {
            millisecondsAgo: new Date() - event.timeStamp,
            type: event.type,
            which: event.which,
            target: targetToString(event.target)
        };
    }

    // Convert a DOM element into a string.
    function targetToString(target) {
        if (target) {
            var attrs = target.attributes;

            if (attrs) {
                var ret = "<" + target.nodeName.toLowerCase();
                for (var i = 0; i < attrs.length; i++) {
                    if (attrs[i].value && attrs[i].value.toString() != "null") {
                        ret += " " + attrs[i].name + "=\"" + attrs[i].value + "\"";
                    }
                }
                return ret + ">";
            } else {
                // e.g. #document
                return target.nodeName;
            }
        }
    }

    // No re-notify
    function ignoreNextOnError() {
        ignoreOnError += 1;
        window.setTimeout(function () {
            ignoreOnError -= 1;
        });
    }

    // Disable catching on IE < 10 as it destroys stack-traces from generateStackTrace()
    if (!window.atob) {
        shouldCatch = false;

        // Disable catching on browsers that support HTML5 ErrorEvents properly.
        // This lets debug on unhandled exceptions work.
    } else if (window.ErrorEvent) {
        try {
            if (new window.ErrorEvent("test").colno === 0) {
                shouldCatch = false;
            }
        } catch(e){ }
    }

    //
    // ### Polyfilling
    //

    // Add a polyFill to an object
    function polyFill(obj, name, makeReplacement) {
        var original = obj[name];
        obj[name] = makeReplacement(original);
    }

    if (getSetting("autoNotify", true)) {
        //
        // ### Automatic error notification
        //
        polyFill(window, "onerror", function (_super) {
            return function jslogger(message, url, lineNo, charNo, exception) {
                var shouldNotify = getSetting("autoNotify", true);
                var metaData = {};

                // IE 6+ support.
                if (!charNo && window.event) {
                    charNo = window.event.errorCharacter;
                }

                addScriptToMetaData(metaData);
                lastScript = null;

                if (shouldNotify && !ignoreOnError) {

                    sendToBackend({
                        name: exception && exception.name || "window.onerror",
                        message: message,
                        file: url,
                        lineNumber: lineNo,
                        columnNumber: charNo,
                        stacktrace: (exception && stacktraceFromException(exception)) || generateStacktrace(),
                        level: "error"
                    }, metaData);
                }

                // Fire the existing `window.onerror` handler, if one exists
                if (_super) {
                    _super(message, url, lineNo, charNo, exception);
                }
            };
        });

        var hijackTimeFunc = function (_super) {
            //_super.call because that doesn't work on IE 8,
            return function (f, t) {
                if (typeof f === "function") {
                    f = wrap(f);
                    var args = Array.prototype.slice.call(arguments, 2);
                    return _super(function () {
                        f.apply(this, args);
                    }, t);
                } else {
                    return _super(f, t);
                }
            };
        };

        polyFill(window, "setTimeout", hijackTimeFunc);
        polyFill(window, "setInterval", hijackTimeFunc);

        if (window.requestAnimationFrame) {
            polyFill(window, "requestAnimationFrame", function (_super) {
                return function (callback) {
                    return _super(wrap(callback));
                };
            });
        }

        if (window.setImmediate) {
            polyFill(window, "setImmediate", function (_super) {
                return function (f) {
                    var args = Array.prototype.slice.call(arguments);
                    args[0] = wrap(args[0]);
                    return _super.apply(this, args);
                };
            });
        }

        "EventTarget Window Node ApplicationCache AudioTrackList ChannelMergerNode CryptoOperation EventSource FileReader HTMLUnknownElement IDBDatabase IDBRequest IDBTransaction KeyOperation MediaController MessagePort ModalWindow Notification SVGElementInstance Screen TextTrack TextTrackCue TextTrackList WebSocket WebSocketWorker Worker XMLHttpRequest XMLHttpRequestEventTarget XMLHttpRequestUpload".replace(/\w+/g, function (global) {
            var prototype = window[global] && window[global].prototype;
            if (prototype && prototype.hasOwnProperty && prototype.hasOwnProperty("addEventListener")) {
                polyFill(prototype, "addEventListener", function (_super) {
                    return function (e, f, capture, secure) {
                        // HTML lets event-handlers be objects with a handleEvent function,
                        try {
                            if (f && f.handleEvent) {
                                f.handleEvent = wrap(f.handleEvent, {eventHandler: true});
                            }
                        } catch (err) {
                            log(err);
                        }
                        return _super.call(this, e, wrap(f, {eventHandler: true}), capture, secure);
                    };
                });

                polyFill(prototype, "removeEventListener", function (_super) {
                    return function (e, f, capture, secure) {
                        _super.call(this, e, f, capture, secure);
                        return _super.call(this, e, wrap(f), capture, secure);
                    };
                });
            }
        });
    }

    window.JsLogger = self;

})(window, window.JsLogger);