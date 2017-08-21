---
title: channelAdminLogEventActionChangeStickerSet
description: channelAdminLogEventActionChangeStickerSet attributes, type and example
---
## Constructor: channelAdminLogEventActionChangeStickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|
|new\_stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionChangeStickerSet = ['_' => 'channelAdminLogEventActionChangeStickerSet', 'prev_stickerset' => InputStickerSet, 'new_stickerset' => InputStickerSet];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionChangeStickerSet", "prev_stickerset": InputStickerSet, "new_stickerset": InputStickerSet}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionChangeStickerSet={_='channelAdminLogEventActionChangeStickerSet', prev_stickerset=InputStickerSet, new_stickerset=InputStickerSet}

```


