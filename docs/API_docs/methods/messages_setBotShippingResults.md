---
title: messages.setBotShippingResults
description: messages.setBotShippingResults parameters, return type and example
---
## Method: messages.setBotShippingResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Yes|
|error|[string](../types/string.md) | Optional|
|shipping\_options|Array of [ShippingOption](../types/ShippingOption.md) | Optional|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->setBotShippingResults(['query_id' => long, 'error' => string, 'shipping_options' => [ShippingOption], ]);
```

Or, if you're into Lua:

```
Bool = messages.setBotShippingResults({query_id=long, error=string, shipping_options={ShippingOption}, })
```

