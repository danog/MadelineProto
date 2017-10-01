---
title: channels.exportMessageLink
description: channels.exportMessageLink parameters, return type and example
---
## Method: channels.exportMessageLink  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|id|[int](../types/int.md) | Yes|


### Return type: [ExportedMessageLink](../types/ExportedMessageLink.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$ExportedMessageLink = $MadelineProto->channels->exportMessageLink(['channel' => InputChannel, 'id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.exportMessageLink`

Parameters:

channel - Json encoded InputChannel

id - Json encoded int




Or, if you're into Lua:

```
ExportedMessageLink = channels.exportMessageLink({channel=InputChannel, id=int, })
```

