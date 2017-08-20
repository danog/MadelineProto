---
title: chat info
description: chat attributes, type and example
---
## Constructor: Info  



### Attributes:

| Name     |    Type       | Required |Description|
|----------|---------------|----------|-----------|
|type|[string](API_docs/types/string.md) | Yes|bot, user, channel, supergroup or chat|
|bot\_api\_id|[long](API_docs/types/long.md) | Yes|bot API chat id|
|InputPeer|[InputPeer](API_docs/types/InputPeer.md) | Yes|InputPeer object|
|Peer|[Peer](API_docs/types/Peer.md) | Optional|Peer object|
|user\_id|[int](API_docs/types/int.md) | Optional|MTProto user id|
|chat\_id|[int](API_docs/types/int.md) | Optional|MTProto chat id|
|channel\_id|[int](API_docs/types/int.md) | Optional|MTProto channel id|
|InputUser|[InputUser](API_docs/types/InputUser.md) | Optional|InputUser object|
|InputChannel|[InputChannel](API_docs/types/InputChannel.md) | Optional|InputChannel object|


