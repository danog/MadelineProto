---
title: phone.saveCallDebug
description: phone.saveCallDebug parameters, return type and example
---
## Method: phone.saveCallDebug  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[CLICK ME InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|debug|[CLICK ME DataJSON](../types/DataJSON.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CALL_PEER_INVALID|The provided call peer object is invalid|
|DATA_JSON_INVALID|The provided JSON data is invalid|


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

$Bool = $MadelineProto->phone->saveCallDebug(['peer' => InputPhoneCall, 'debug' => DataJSON, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.saveCallDebug`

Parameters:

peer - Json encoded InputPhoneCall

debug - Json encoded DataJSON




Or, if you're into Lua:

```
Bool = phone.saveCallDebug({peer=InputPhoneCall, debug=DataJSON, })
```

