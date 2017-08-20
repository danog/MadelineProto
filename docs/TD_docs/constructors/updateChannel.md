---
title: updateChannel
description: Some data about a channel has been changed
---
## Constructor: updateChannel  
[Back to constructors index](index.md)



Some data about a channel has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[channel](../types/channel.md) | Yes|New data about the channel|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannel = ['_' => 'updateChannel', 'channel' => channel];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannel", "channel": channel}
```


Or, if you're into Lua:  


```
updateChannel={_='updateChannel', channel=channel}

```


