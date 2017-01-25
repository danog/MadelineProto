---
title: peerUser
description: peerUser attributes, type and example
---
## Constructor: peerUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|



### Type: [Peer](../types/Peer.md)


### Example:

```
$peerUser = ['_' => 'peerUser', 'user_id' => int, ];
```  

The following syntaxes can also be used:

```
$peerUser = '@username'; // Username

$peerUser = 44700; // bot API id (users)
$peerUser = -492772765; // bot API id (chats)
$peerUser = -10038575794; // bot API id (channels)

$peerUser = 'user#44700'; // tg-cli style id (users)
$peerUser = 'chat#492772765'; // tg-cli style id (chats)
$peerUser = 'channel#38575794'; // tg-cli style id (channels)
```