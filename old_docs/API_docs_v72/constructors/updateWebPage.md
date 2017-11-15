---
title: updateWebPage
description: updateWebPage attributes, type and example
---
## Constructor: updateWebPage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|webpage|[WebPage](../types/WebPage.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateWebPage = ['_' => 'updateWebPage', 'webpage' => WebPage, 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateWebPage", "webpage": WebPage, "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateWebPage={_='updateWebPage', webpage=WebPage, pts=int, pts_count=int}

```


