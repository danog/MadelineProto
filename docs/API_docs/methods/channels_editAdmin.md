---
title: channels.editAdmin
description: channels.editAdmin parameters, return type and example
---
## Method: channels.editAdmin  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|admin\_rights|[ChannelAdminRights](../types/ChannelAdminRights.md) | Yes|


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

$Updates = $MadelineProto->channels->editAdmin(['channel' => InputChannel, 'user_id' => InputUser, 'admin_rights' => ChannelAdminRights, ]);
```

Or, if you're into Lua:

```
Updates = channels.editAdmin({channel=InputChannel, user_id=InputUser, admin_rights=ChannelAdminRights, })
```

