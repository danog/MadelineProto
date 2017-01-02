---
title: inputPeerForeign
description: inputPeerForeign attributes, type and example
---
## Constructor: inputPeerForeign  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerForeign = ['_' => 'inputPeerForeign', 'user_id' => int, 'access_hash' => long, ];
```  

The following syntaxes can also be used:

```
$inputPeerForeign = '@username'; // Username

$inputPeerForeign = 44700; // bot API id (users)
$inputPeerForeign = -492772765; // bot API id (chats)
$inputPeerForeign = -10038575794; // bot API id (channels)

$inputPeerForeign = 'user#44700'; // tg-cli style id (users)
$inputPeerForeign = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerForeign = 'channel#38575794'; // tg-cli style id (channels)
```