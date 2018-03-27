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
|query\_id|[long](../types/long.md) | Yes|The query ID|
|data|[DataJSON](../types/DataJSON.md) | Yes|The parameters|


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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|
|USER_BOT_INVALID|This method can only be called by a bot|


