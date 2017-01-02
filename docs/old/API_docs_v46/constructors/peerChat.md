---
title: peerChat
description: peerChat attributes, type and example
---
## Constructor: peerChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|



### Type: [Peer](../types/Peer.md)


### Example:

```
$peerChat = ['_' => 'peerChat', 'chat_id' => int, ];
```  

The following syntaxes can also be used:

```
$peerChat = '@username'; // Username

$peerChat = 44700; // bot API id (users)
$peerChat = -492772765; // bot API id (chats)
$peerChat = -10038575794; // bot API id (channels)

$peerChat = 'user#44700'; // tg-cli style id (users)
$peerChat = 'chat#492772765'; // tg-cli style id (chats)
$peerChat = 'channel#38575794'; // tg-cli style id (channels)
```