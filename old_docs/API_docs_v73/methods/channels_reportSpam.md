---
title: channels.reportSpam
description: channels.reportSpam parameters, return type and example
---
## Method: channels.reportSpam  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Optional|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->channels->reportSpam(['channel' => InputChannel, 'user_id' => InputUser, 'id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.reportSpam`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

id - Json encoded  array of int




Or, if you're into Lua:

```
Bool = channels.reportSpam({channel=InputChannel, user_id=InputUser, id={int}, })
```

