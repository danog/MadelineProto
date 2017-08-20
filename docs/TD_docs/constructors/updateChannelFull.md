---
title: updateChannelFull
description: Some data from channelFull has been changed
---
## Constructor: updateChannelFull  
[Back to constructors index](index.md)



Some data from channelFull has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_full|[channelFull](../types/channelFull.md) | Yes|New full information about the channel|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelFull = ['_' => 'updateChannelFull', 'channel_full' => channelFull];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelFull", "channel_full": channelFull}
```


Or, if you're into Lua:  


```
updateChannelFull={_='updateChannelFull', channel_full=channelFull}

```


