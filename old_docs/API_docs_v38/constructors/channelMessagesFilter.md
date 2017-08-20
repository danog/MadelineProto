---
title: channelMessagesFilter
description: channelMessagesFilter attributes, type and example
---
## Constructor: channelMessagesFilter  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ranges|Array of [MessageRange](../types/MessageRange.md) | Yes|



### Type: [ChannelMessagesFilter](../types/ChannelMessagesFilter.md)


### Example:

```
$channelMessagesFilter = ['_' => 'channelMessagesFilter', 'ranges' => [MessageRange]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelMessagesFilter", "ranges": [MessageRange]}
```


Or, if you're into Lua:  


```
channelMessagesFilter={_='channelMessagesFilter', ranges={MessageRange}}

```


