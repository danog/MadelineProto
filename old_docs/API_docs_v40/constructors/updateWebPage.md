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



### Type: [Update](../types/Update.md)


### Example:

```
$updateWebPage = ['_' => 'updateWebPage', 'webpage' => WebPage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateWebPage", "webpage": WebPage}
```


Or, if you're into Lua:  


```
updateWebPage={_='updateWebPage', webpage=WebPage}

```


