---
title: get_pwr_chat
description: get_pwr_chat parameters, return type and example
---
## Method: get_pwr_chat  


### Parameters:

| Name     |    Type       |
|----------|---------------|
|id| A username, a bot API chat id, a tg-cli chat id, a [Chat](API_docs/types/Chat.md), a [User](API_docs/types/User.md), an [InputPeer](API_docs/types/InputPeer.md), an [InputUser](API_docs/types/InputUser.md), an [InputChannel](API_docs/types/InputChannel.md), a [Peer](API_docs/types/Peer.md), or a [Chat](API_docs/types/Chat.md) object|
|fullfetch| Optional, a boolean that if set to true (the default) fetches full info (chat photo, invite link, bot info, common_chats_count, phone_calls_available, phone_calls_private, can_set_username, can_view_participants, participants)|

### Return type: [PWRTelegram Chat](Chat.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->start();

$Chat = $MadelineProto->get_pwr_chat($id);
```

Or, if you're into Lua:

```
Chat = get_pwr_chat(id)
```

