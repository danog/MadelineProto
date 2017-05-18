---
title: phone.getCallConfig
description: phone.getCallConfig parameters, return type and example
---
## Method: phone.getCallConfig  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|


### Return type: [DataJSON](../types/DataJSON.md)

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

$DataJSON = $MadelineProto->phone->getCallConfig();
```

Or, if you're into Lua:

```
DataJSON = phone.getCallConfig({})
```

