---
title: messages.setBotShippingResults
description: Bots only: set shipping results
---
## Method: messages.setBotShippingResults  
[Back to methods index](index.md)


Bots only: set shipping results

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query\_id|[long](../types/long.md) | Yes|Query ID|
|error|[string](../types/string.md) | Optional|Error|
|shipping\_options|Array of [ShippingOption](../types/ShippingOption.md) | Optional|Shipping options|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->setBotShippingResults(['query_id' => long, 'error' => 'string', 'shipping_options' => [ShippingOption, ShippingOption], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|


