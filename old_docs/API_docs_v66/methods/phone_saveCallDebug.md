---
title: phone.saveCallDebug
description: Save call debugging info
---
## Method: phone.saveCallDebug  
[Back to methods index](index.md)


Save call debugging info

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|The call|
|debug|[DataJSON](../types/DataJSON.md) | Yes|Debugging info|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CALL_PEER_INVALID|The provided call peer object is invalid|
|DATA_JSON_INVALID|The provided JSON data is invalid|


