---
title: messages.stickerSetInstallResultArchive
description: messages_stickerSetInstallResultArchive attributes, type and example
---
## Constructor: messages.stickerSetInstallResultArchive  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sets|Array of [StickerSetCovered](../types/StickerSetCovered.md) | Yes|



### Type: [messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)


### Example:

```
$messages_stickerSetInstallResultArchive = ['_' => 'messages.stickerSetInstallResultArchive', 'sets' => [StickerSetCovered]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.stickerSetInstallResultArchive", "sets": [StickerSetCovered]}
```


Or, if you're into Lua:  


```
messages_stickerSetInstallResultArchive={_='messages.stickerSetInstallResultArchive', sets={StickerSetCovered}}

```


