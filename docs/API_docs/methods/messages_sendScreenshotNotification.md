---
title: messages.sendScreenshotNotification
description: messages.sendScreenshotNotification parameters, return type and example
---
## Method: messages.sendScreenshotNotification  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->sendScreenshotNotification(['peer' => InputPeer, 'reply_to_msg_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendScreenshotNotification`

Parameters:

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int




Or, if you're into Lua:

```
Updates = messages.sendScreenshotNotification({peer=InputPeer, reply_to_msg_id=int, })
```

