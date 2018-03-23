---
title: bots.answerWebhookJSONQuery
description: Send webhook request via bot API
---
## Method: bots.answerWebhookJSONQuery  
[Back to methods index](index.md)


Send webhook request via bot API

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query\_id|[CLICK ME long](../types/long.md) | Yes|The query ID|
|data|[CLICK ME DataJSON](../types/DataJSON.md) | Yes|The parameters|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|
|USER_BOT_INVALID|This method can only be called by a bot|


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

$Bool = $MadelineProto->bots->answerWebhookJSONQuery(['query_id' => long, 'data' => DataJSON, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

