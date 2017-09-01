---
title: messages.setBotShippingResults
description: messages.setBotShippingResults parameters, return type and example
---
## Method: messages.setBotShippingResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[long](../types/long.md) | Yes|
|error|[string](../types/string.md) | Optional|
|shipping\_options|Array of [ShippingOption](../types/ShippingOption.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|


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

$Bool = $MadelineProto->messages->setBotShippingResults(['query_id' => long, 'error' => 'string', 'shipping_options' => [ShippingOption], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setBotShippingResults
* params - `{"query_id": long, "error": "string", "shipping_options": [ShippingOption], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setBotShippingResults`

Parameters:

query_id - Json encoded long

error - Json encoded string

shipping_options - Json encoded  array of ShippingOption




Or, if you're into Lua:

```
Bool = messages.setBotShippingResults({query_id=long, error='string', shipping_options={ShippingOption}, })
```

