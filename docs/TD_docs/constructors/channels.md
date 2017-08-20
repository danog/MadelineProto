---
title: channels
description: Contains list of channel identifiers
---
## Constructor: channels  
[Back to constructors index](index.md)



Contains list of channel identifiers

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_ids|Array of [int](../constructors/int.md) | Yes|List of channel identifiers|



### Type: [Channels](../types/Channels.md)


### Example:

```
$channels = ['_' => 'channels', 'channel_ids' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channels", "channel_ids": [int]}
```


Or, if you're into Lua:  


```
channels={_='channels', channel_ids={int}}

```


