---
title: pageBlockBlockquote
description: pageBlockBlockquote attributes, type and example
---
## Constructor: pageBlockBlockquote  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|text|[RichText](../types/RichText.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockBlockquote = ['_' => 'pageBlockBlockquote', 'text' => RichText, 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockBlockquote", "text": RichText, "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockBlockquote={_='pageBlockBlockquote', text=RichText, caption=RichText}

```


