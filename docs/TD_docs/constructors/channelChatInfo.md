---
title: channelChatInfo
description: Chat with unlimited number of members
---
## Constructor: channelChatInfo  
[Back to constructors index](index.md)



Chat with unlimited number of members

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[channel](../types/channel.md) | Yes|Information about the chat|



### Type: [ChatInfo](../types/ChatInfo.md)


### Example:

```
$channelChatInfo = ['_' => 'channelChatInfo', 'channel' => channel];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelChatInfo", "channel": channel}
```


Or, if you're into Lua:  


```
channelChatInfo={_='channelChatInfo', channel=channel}

```


