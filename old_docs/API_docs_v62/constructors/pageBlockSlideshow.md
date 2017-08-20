---
title: pageBlockSlideshow
description: pageBlockSlideshow attributes, type and example
---
## Constructor: pageBlockSlideshow  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|items|Array of [PageBlock](../types/PageBlock.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockSlideshow = ['_' => 'pageBlockSlideshow', 'items' => [PageBlock], 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockSlideshow", "items": [PageBlock], "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockSlideshow={_='pageBlockSlideshow', items={PageBlock}, caption=RichText}

```


