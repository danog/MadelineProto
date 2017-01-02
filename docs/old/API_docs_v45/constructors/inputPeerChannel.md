---
title: inputPeerChannel
description: inputPeerChannel attributes, type and example
---
## Constructor: inputPeerChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerChannel = ['_' => 'inputPeerChannel', 'channel_id' => int, 'access_hash' => long, ];
```  

The following syntaxes can also be used:

```
$inputPeerChannel = '@username'; // Username

$inputPeerChannel = 44700; // bot API id (users)
$inputPeerChannel = -492772765; // bot API id (chats)
$inputPeerChannel = -10038575794; // bot API id (channels)

$inputPeerChannel = 'user#44700'; // tg-cli style id (users)
$inputPeerChannel = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerChannel = 'channel#38575794'; // tg-cli style id (channels)
```