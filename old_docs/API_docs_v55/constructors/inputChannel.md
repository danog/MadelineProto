---
title: inputChannel
description: inputChannel attributes, type and example
---
## Constructor: inputChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputChannel](../types/InputChannel.md)


### Example:

```
$inputChannel = ['_' => 'inputChannel', 'channel_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputChannel", "channel_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputChannel={_='inputChannel', channel_id=int, access_hash=long}

```


