---
title: updateChannelWebPage
description: updateChannelWebPage attributes, type and example
---
## Constructor: updateChannelWebPage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|webpage|[WebPage](../types/WebPage.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelWebPage = ['_' => 'updateChannelWebPage', 'channel_id' => int, 'webpage' => WebPage, 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelWebPage", "channel_id": int, "webpage": WebPage, "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateChannelWebPage={_='updateChannelWebPage', channel_id=int, webpage=WebPage, pts=int, pts_count=int}

```


