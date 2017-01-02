---
title: inputPeerSelf
description: inputPeerSelf attributes, type and example
---
## Constructor: inputPeerSelf  
[Back to constructors index](index.md)






### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerSelf = ['_' => 'inputPeerSelf', ];
```  

The following syntaxes can also be used:

```
$inputPeerSelf = '@username'; // Username

$inputPeerSelf = 44700; // bot API id (users)
$inputPeerSelf = -492772765; // bot API id (chats)
$inputPeerSelf = -10038575794; // bot API id (channels)

$inputPeerSelf = 'user#44700'; // tg-cli style id (users)
$inputPeerSelf = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerSelf = 'channel#38575794'; // tg-cli style id (channels)
```