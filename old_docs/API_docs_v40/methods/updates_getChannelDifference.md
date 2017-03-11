---
title: updates.getChannelDifference
description: updates.getChannelDifference parameters, return type and example
---
## Method: updates.getChannelDifference  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|filter|[ChannelMessagesFilter](../types/ChannelMessagesFilter.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$updates_ChannelDifference = $MadelineProto->updates->getChannelDifference(['peer' => InputPeer, 'filter' => ChannelMessagesFilter, 'pts' => int, 'limit' => int, ]);
```

Or, if you're into Lua:

```
updates_ChannelDifference = updates.getChannelDifference({peer=InputPeer, filter=ChannelMessagesFilter, pts=int, limit=int, })
```

