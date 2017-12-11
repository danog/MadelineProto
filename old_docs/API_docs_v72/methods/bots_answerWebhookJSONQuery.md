---
title: bots.answerWebhookJSONQuery
description: bots.answerWebhookJSONQuery parameters, return type and example
---
## Method: bots.answerWebhookJSONQuery  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[long](../types/long.md) | Yes|
|data|[DataJSON](../types/DataJSON.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|
|USER_BOT_INVALID|This method can only be called by a bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->bots->answerWebhookJSONQuery(['query_id' => long, 'data' => DataJSON, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - bots.answerWebhookJSONQuery
* params - `{"query_id": long, "data": DataJSON, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/bots.answerWebhookJSONQuery`

Parameters:

query_id - Json encoded long

data - Json encoded DataJSON




Or, if you're into Lua:

```
Bool = bots.answerWebhookJSONQuery({query_id=long, data=DataJSON, })
```

