---
title: request_secret_chat
description: request_secret_chat parameters, return type and example
---
## Method: request_secret_chat  


### Parameters:

| Name     |    Type       |
|----------|---------------|
|id| A username, a bot API chat id, a tg-cli chat id, a [User](API_docs/types/User.md), an [InputPeer](API_docs/types/InputPeer.md), an [InputUser](API_docs/types/InputUser.md), a [Peer](API_docs/types/Peer.md)|

### Return type: Number

Returns the secret chat ID

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->start();

$secret_chat_id = $MadelineProto->request_secret_chat('@danogentili');
```

