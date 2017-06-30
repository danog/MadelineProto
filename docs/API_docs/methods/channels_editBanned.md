---
title: channels.editBanned
description: channels.editBanned parameters, return type and example
---
## Method: channels.editBanned  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|banned\_rights|[ChannelBannedRights](../types/ChannelBannedRights.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->channels->editBanned(['channel' => InputChannel, 'user_id' => InputUser, 'banned_rights' => ChannelBannedRights, ]);
```

Or, if you're into Lua:

```
Updates = channels.editBanned({channel=InputChannel, user_id=InputUser, banned_rights=ChannelBannedRights, })
```

