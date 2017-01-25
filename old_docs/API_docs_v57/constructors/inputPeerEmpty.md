---
title: inputPeerEmpty
description: inputPeerEmpty attributes, type and example
---
## Constructor: inputPeerEmpty  
[Back to constructors index](index.md)






### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerEmpty = ['_' => 'inputPeerEmpty', ];
```  

The following syntaxes can also be used:

```
$inputPeerEmpty = '@username'; // Username

$inputPeerEmpty = 44700; // bot API id (users)
$inputPeerEmpty = -492772765; // bot API id (chats)
$inputPeerEmpty = -10038575794; // bot API id (channels)

$inputPeerEmpty = 'user#44700'; // tg-cli style id (users)
$inputPeerEmpty = 'chat#492772765'; // tg-cli style id (chats)
$inputPeerEmpty = 'channel#38575794'; // tg-cli style id (channels)
```