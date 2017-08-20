---
title: getChannelMembers
description: Returns information about channel members or kicked from channel users. Can be used only if channel_full->can_get_members == true
---
## Method: getChannelMembers  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about channel members or kicked from channel users. Can be used only if channel_full->can_get_members == true

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|
|filter|[ChannelMembersFilter](../types/ChannelMembersFilter.md) | Yes|Kind of channel users to return, defaults to channelMembersRecent|
|offset|[int](../types/int.md) | Yes|Number of channel users to skip|
|limit|[int](../types/int.md) | Yes|Maximum number of users be returned, can't be greater than 200|


### Return type: [ChatMembers](../types/ChatMembers.md)

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

$ChatMembers = $MadelineProto->getChannelMembers(['channel_id' => int, 'filter' => ChannelMembersFilter, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getChannelMembers
* params - `{"channel_id": int, "filter": ChannelMembersFilter, "offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getChannelMembers`

Parameters:

channel_id - Json encoded int

filter - Json encoded ChannelMembersFilter

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
ChatMembers = getChannelMembers({channel_id=int, filter=ChannelMembersFilter, offset=int, limit=int, })
```

