---
title: phone.receivedCall
description: Notify server that you received a call (server will refuse all incoming calls until the current call is over)
---
## Method: phone.receivedCall  
[Back to methods index](index.md)


Notify server that you received a call (server will refuse all incoming calls until the current call is over)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|The phone call you received|


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

$Bool = $MadelineProto->phone->receivedCall(['peer' => InputPhoneCall, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.receivedCall`

Parameters:

peer - Json encoded InputPhoneCall




Or, if you're into Lua:

```
Bool = phone.receivedCall({peer=InputPhoneCall, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CALL_ALREADY_DECLINED|The call was already declined|
|CALL_PEER_INVALID|The provided call peer object is invalid|


