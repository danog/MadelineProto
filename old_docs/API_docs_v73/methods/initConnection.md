---
title: initConnection
description: Initializes connection and save information on the user's device and application.
---
## Method: initConnection  
[Back to methods index](index.md)


Initializes connection and save information on the user's device and application.

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|api\_id|[int](../types/int.md) | Yes|Application identifier|
|device\_model|[string](../types/string.md) | Yes|Device model|
|system\_version|[string](../types/string.md) | Yes|System version|
|app\_version|[string](../types/string.md) | Yes|App version|
|system\_lang\_code|[string](../types/string.md) | Yes|Language code|
|lang\_pack|[string](../types/string.md) | Yes|Language pack to use|
|lang\_code|[string](../types/string.md) | Yes|Language code to set|
|query|[!X](../types/!X.md) | Yes|Nested query|


### Return type: [X](../types/X.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CONNECTION_LAYER_INVALID|Layer invalid|
|INPUT_FETCH_FAIL|Failed deserializing TL payload|


### Example:


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

$X = $MadelineProto->initConnection(['api_id' => int, 'device_model' => 'string', 'system_version' => 'string', 'app_version' => 'string', 'system_lang_code' => 'string', 'lang_pack' => 'string', 'lang_code' => 'string', 'query' => !X, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - initConnection
* params - `{"api_id": int, "device_model": "string", "system_version": "string", "app_version": "string", "system_lang_code": "string", "lang_pack": "string", "lang_code": "string", "query": !X, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/initConnection`

Parameters:

api_id - Json encoded int

device_model - Json encoded string

system_version - Json encoded string

app_version - Json encoded string

system_lang_code - Json encoded string

lang_pack - Json encoded string

lang_code - Json encoded string

query - Json encoded !X




Or, if you're into Lua:

```
X = initConnection({api_id=int, device_model='string', system_version='string', app_version='string', system_lang_code='string', lang_pack='string', lang_code='string', query=!X, })
```

