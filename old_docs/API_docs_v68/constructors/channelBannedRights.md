---
title: channelBannedRights
description: channelBannedRights attributes, type and example
---
## Constructor: channelBannedRights  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|view\_messages|[Bool](../types/Bool.md) | Optional|
|send\_messages|[Bool](../types/Bool.md) | Optional|
|send\_media|[Bool](../types/Bool.md) | Optional|
|send\_stickers|[Bool](../types/Bool.md) | Optional|
|send\_gifs|[Bool](../types/Bool.md) | Optional|
|send\_games|[Bool](../types/Bool.md) | Optional|
|send\_inline|[Bool](../types/Bool.md) | Optional|
|embed\_links|[Bool](../types/Bool.md) | Optional|
|until\_date|[int](../types/int.md) | Yes|



### Type: [ChannelBannedRights](../types/ChannelBannedRights.md)


### Example:

```
$channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => Bool, 'send_messages' => Bool, 'send_media' => Bool, 'send_stickers' => Bool, 'send_gifs' => Bool, 'send_games' => Bool, 'send_inline' => Bool, 'embed_links' => Bool, 'until_date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelBannedRights", "view_messages": Bool, "send_messages": Bool, "send_media": Bool, "send_stickers": Bool, "send_gifs": Bool, "send_games": Bool, "send_inline": Bool, "embed_links": Bool, "until_date": int}
```


Or, if you're into Lua:  


```
channelBannedRights={_='channelBannedRights', view_messages=Bool, send_messages=Bool, send_media=Bool, send_stickers=Bool, send_gifs=Bool, send_games=Bool, send_inline=Bool, embed_links=Bool, until_date=int}

```


