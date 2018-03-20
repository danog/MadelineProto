---
title: messages.setBotShippingResults
description: messages.setBotShippingResults parameters, return type and example
---
## Method: messages.setBotShippingResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[CLICK ME long](../types/long.md) | Yes|
|error|[CLICK ME string](../types/string.md) | Optional|
|shipping\_options|Array of [CLICK ME ShippingOption](../types/ShippingOption.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
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

