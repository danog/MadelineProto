---
title: get_full_info
description: get_full_info parameters, return type and example
---
## Method: get_full_info  


### Parameters:

| Name     |    Type       |
|----------|---------------|
|id| A username, a bot API chat id, a tg-cli chat id, a [Chat](API_docs/types/Chat.md), a [User](API_docs/types/User.md), an [InputPeer](API_docs/types/InputPeer.md), an [InputUser](API_docs/types/InputUser.md), an [InputChannel](API_docs/types/InputChannel.md), a [Peer](API_docs/types/Peer.md), or a [Chat](API_docs/types/Chat.md) object|

### Return type: [FullInfo](FullInfo.md)

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

$Chat = $MadelineProto->get_full_info($id);
```

Or, if you're into Lua:

```
Chat = get_full_info(id)
```

