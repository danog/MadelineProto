---
title: pageBlockCollage
description: pageBlockCollage attributes, type and example
---
## Constructor: pageBlockCollage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|items|Array of [PageBlock](../types/PageBlock.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockCollage = ['_' => 'pageBlockCollage', 'items' => [PageBlock], 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockCollage", "items": [PageBlock], "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockCollage={_='pageBlockCollage', items={PageBlock}, caption=RichText}

```


