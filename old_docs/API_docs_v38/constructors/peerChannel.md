---
title: peerChannel
description: peerChannel attributes, type and example
---
## Constructor: peerChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|



### Type: [Peer](../types/Peer.md)


### Example:

```
$peerChannel = ['_' => 'peerChannel', 'channel_id' => int, ];
```  

The following syntaxes can also be used:

```
$peerChannel = '@username'; // Username

$peerChannel = 44700; // bot API id (users)
$peerChannel = -492772765; // bot API id (chats)
$peerChannel = -10038575794; // bot API id (channels)

$peerChannel = 'user#44700'; // tg-cli style id (users)
$peerChannel = 'chat#492772765'; // tg-cli style id (chats)
$peerChannel = 'channel#38575794'; // tg-cli style id (channels)
```