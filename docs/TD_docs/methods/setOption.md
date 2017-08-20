---
title: setOption
description: Sets value of an option. See list of available options on https: core.telegram.org/tdlib/options. Only writable options can be set
---
## Method: setOption  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sets value of an option. See list of available options on https: core.telegram.org/tdlib/options. Only writable options can be set

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|name|[string](../types/string.md) | Yes|Name of the option|
|value|[OptionValue](../types/OptionValue.md) | Yes|New value of the option|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->setOption(['name' => 'string', 'value' => OptionValue, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setOption
* params - `{"name": "string", "value": OptionValue, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setOption`

Parameters:

name - Json encoded string

value - Json encoded OptionValue




Or, if you're into Lua:

```
Ok = setOption({name='string', value=OptionValue, })
```

