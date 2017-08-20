---
title: toggleChannelInvites
description: Gives or revokes right to invite new members to all current members of the channel. Needs creator privileges in the channel. Available only for supergroups
---
## Method: toggleChannelInvites  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Gives or revokes right to invite new members to all current members of the channel. Needs creator privileges in the channel. Available only for supergroups

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|
|anyone\_can\_invite|[Bool](../types/Bool.md) | Yes|New value of anyone_can_invite|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->toggleChannelInvites(['channel_id' => int, 'anyone_can_invite' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - toggleChannelInvites
* params - `{"channel_id": int, "anyone_can_invite": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/toggleChannelInvites`

Parameters:

channel_id - Json encoded int

anyone_can_invite - Json encoded Bool




Or, if you're into Lua:

```
Ok = toggleChannelInvites({channel_id=int, anyone_can_invite=Bool, })
```

