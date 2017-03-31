---
title: help.getAppChangelog
description: help.getAppChangelog parameters, return type and example
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|prev\_app\_version|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->help->getAppChangelog(['prev_app_version' => string, ]);
```

Or, if you're into Lua:

```
Updates = help.getAppChangelog({prev_app_version=string, })
```

