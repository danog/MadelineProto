---
title: changeChatDraftMessage
description: Changes chat draft message
---
## Method: changeChatDraftMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat draft message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|draft\_message|[draftMessage](../types/draftMessage.md) | Yes|New draft message, nullable|


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

$Ok = $MadelineProto->changeChatDraftMessage(['chat_id' => InputPeer, 'draft_message' => draftMessage, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChatDraftMessage
* params - `{"chat_id": InputPeer, "draft_message": draftMessage, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChatDraftMessage`

Parameters:

chat_id - Json encoded InputPeer

draft_message - Json encoded draftMessage




Or, if you're into Lua:

```
Ok = changeChatDraftMessage({chat_id=InputPeer, draft_message=draftMessage, })
```

