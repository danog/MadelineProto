---
title: updates.channelDifferenceEmpty
description: updates_channelDifferenceEmpty attributes, type and example
---
## Constructor: updates.channelDifferenceEmpty  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|final|[Bool](../types/Bool.md) | Optional|
|pts|[int](../types/int.md) | Yes|
|timeout|[int](../types/int.md) | Optional|



### Type: [updates\_ChannelDifference](../types/updates_ChannelDifference.md)


### Example:

```
$updates_channelDifferenceEmpty = ['_' => 'updates.channelDifferenceEmpty', 'final' => Bool, 'pts' => int, 'timeout' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.channelDifferenceEmpty", "final": Bool, "pts": int, "timeout": int}
```


Or, if you're into Lua:  


```
updates_channelDifferenceEmpty={_='updates.channelDifferenceEmpty', final=Bool, pts=int, timeout=int}

```


