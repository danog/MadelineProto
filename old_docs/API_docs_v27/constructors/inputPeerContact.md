---
title: inputPeerContact
description: inputPeerContact attributes, type and example
---
## Constructor: inputPeerContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerContact = ['_' => 'inputPeerContact', 'user_id' => int, ];
```  

The following syntaxes can also be used:

```
$inputPeerContact = '@username'; // Username

$inputPeerContact = 44700; // bot API id (users)
$inputPeerContact = -492772765; // bot API id (chats)
$inputPeerContact = -10038575794; // bot API id (channels)

$inputPeerContact = 'user#44700'; // tg-cli style id (users)
$inputPeerContact = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerContact = 'channel#38575794'; // tg-cli style id (channels)
```