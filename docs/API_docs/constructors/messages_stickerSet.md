---
title: messages.stickerSet
description: messages_stickerSet attributes, type and example
---
## Constructor: messages.stickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|set|[StickerSet](../types/StickerSet.md) | Required|
|packs|Array of [StickerPack](../types/StickerPack.md) | Required|
|documents|Array of [Document](../types/Document.md) | Required|



### Type: [messages\_StickerSet](../types/messages_StickerSet.md)


### Example:

```
$messages_stickerSet = ['_' => 'messages.stickerSet', 'set' => StickerSet, 'packs' => [Vector t], 'documents' => [Vector t], ];
```  

