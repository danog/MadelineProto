---
title: channelAdminLogEventActionEditMessage
description: channelAdminLogEventActionEditMessage attributes, type and example
---
## Constructor: channelAdminLogEventActionEditMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_message|[Message](../types/Message.md) | Yes|
|new\_message|[Message](../types/Message.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionEditMessage = ['_' => 'channelAdminLogEventActionEditMessage', 'prev_message' => Message, 'new_message' => Message];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionEditMessage", "prev_message": Message, "new_message": Message}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionEditMessage={_='channelAdminLogEventActionEditMessage', prev_message=Message, new_message=Message}

```


