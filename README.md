# JsLoggerBundle
[![Build Status](https://travis-ci.org/da-wen/js-logger-bundle.svg?branch=master)](https://travis-ci.org/da-wen/js-logger-bundle)


This JsLogger Bundle aims to catch error in the frontend and sending them to backend.
All errors will be handled by a JsLogger Service that persists the log into a channel named *javascript* 


---

## Credits

Big thanks goes out to Bugsnag and Nelmio. Thanks for sharing the code that inspired me.

[Bugsnag Javascript](https://github.com/bugsnag/bugsnag-js)

[NelmioJsLoggerBundle](https://github.com/nelmio/NelmioJsLoggerBundle)


---


## Installation

### Step 1: Composer

Required in *composer.json*
    
```json
   "dawen/js-logger-bundle": "~1.0"
```

### Step 2: AppKernel  

In your *app/config/AppHernel.php* file you should activate the bundle by adding it to the array

```php
    $bundles[] = new \Dawen\Bundle\JsLoggerBundle\JsLoggerBundle();
```

### Step 3: Script  

In your twig template you should place the twig method call before all your other javascript is initialized and before the closing body tag

```twig
    {{ js_logger() }}
```



Go ony with configuration section if needed.


---


## Configuration

```yml
    js_logger:
        enabled: true
        allowed_levels: [warning, error]
```

If The configuration sections is not defined, default values will be applied.


### Parameter Description


*enabled:*

possible values: true, false
default value: true
description: If disabled, the JsLogger service will be removed from container and the JsTwigExtension will not dump the needed script tag


*allowed_levels:*

possible values: emergency, alert, critical, error, warning, notice, info, debug
default value: []
description: If an empty array is provided, there will be no restriction. You can restrict the logger pushing the logs to monolog, by setting values.


---


## Developer Informations

For installing and  minifying the jslogger.js simply run from the bundles root path:

```shell
    npm install && npm run build
```