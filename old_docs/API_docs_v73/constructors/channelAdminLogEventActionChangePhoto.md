---
title: channelAdminLogEventActionChangePhoto
description: channelAdminLogEventActionChangePhoto attributes, type and example
---
## Constructor: channelAdminLogEventActionChangePhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_photo|[ChatPhoto](../types/ChatPhoto.md) | Optional|
|new\_photo|[ChatPhoto](../types/ChatPhoto.md) | Optional|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionChangePhoto = ['_' => 'channelAdminLogEventActionChangePhoto', 'prev_photo' => ChatPhoto, 'new_photo' => ChatPhoto];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionChangePhoto", "prev_photo": ChatPhoto, "new_photo": ChatPhoto}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionChangePhoto={_='channelAdminLogEventActionChangePhoto', prev_photo=ChatPhoto, new_photo=ChatPhoto}

```


