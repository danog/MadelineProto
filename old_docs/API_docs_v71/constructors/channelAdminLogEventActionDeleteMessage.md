---
title: channelAdminLogEventActionDeleteMessage
description: channelAdminLogEventActionDeleteMessage attributes, type and example
---
## Constructor: channelAdminLogEventActionDeleteMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionDeleteMessage = ['_' => 'channelAdminLogEventActionDeleteMessage', 'message' => Message];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionDeleteMessage", "message": Message}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionDeleteMessage={_='channelAdminLogEventActionDeleteMessage', message=Message}

```


