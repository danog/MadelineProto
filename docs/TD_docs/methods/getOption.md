---
title: getOption
description: Returns value of an option by its name. See list of available options on https: core.telegram.org/tdlib/options
---
## Method: getOption  
[Back to methods index](index.md)


Returns value of an option by its name. See list of available options on https: core.telegram.org/tdlib/options

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|name|[string](../types/string.md) | Yes|Name of the option|


### Return type: [OptionValue](../types/OptionValue.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$OptionValue = $MadelineProto->getOption(['name' => string, ]);
```

Or, if you're into Lua:

```
OptionValue = getOption({name=string, })
```

