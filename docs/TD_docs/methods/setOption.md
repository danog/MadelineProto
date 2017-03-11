---
title: setOption
description: Sets value of an option. See list of available options on https: core.telegram.org/tdlib/options. Only writable options can be set
---
## Method: setOption  
[Back to methods index](index.md)


Sets value of an option. See list of available options on https: core.telegram.org/tdlib/options. Only writable options can be set

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|name|[string](../types/string.md) | Yes|Name of the option|
|value|[OptionValue](../types/OptionValue.md) | Yes|New value of the option|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->setOption(['name' => string, 'value' => OptionValue, ]);
```

Or, if you're into Lua:

```
Ok = setOption({name=string, value=OptionValue, })
```

