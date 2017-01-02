---
title: inputPeerChat
description: inputPeerChat attributes, type and example
---
## Constructor: inputPeerChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerChat = ['_' => 'inputPeerChat', 'chat_id' => int, ];
```  

The following syntaxes can also be used:

```
$inputPeerChat = '@username'; // Username

$inputPeerChat = 44700; // bot API id (users)
$inputPeerChat = -492772765; // bot API id (chats)
$inputPeerChat = -10038575794; // bot API id (channels)

$inputPeerChat = 'user#44700'; // tg-cli style id (users)
$inputPeerChat = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerChat = 'channel#38575794'; // tg-cli style id (channels)
```