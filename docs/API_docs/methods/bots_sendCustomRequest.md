---
title: bots.sendCustomRequest
description: bots.sendCustomRequest parameters, return type and example
---
## Method: bots.sendCustomRequest  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|custom\_method|[string](../types/string.md) | Yes|
|params|[DataJSON](../types/DataJSON.md) | Yes|


### Return type: [DataJSON](../types/DataJSON.md)

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

$DataJSON = $MadelineProto->bots->sendCustomRequest(['custom_method' => string, 'params' => DataJSON, ]);
```

Or, if you're into Lua:

```
DataJSON = bots.sendCustomRequest({custom_method=string, params=DataJSON, })
```

