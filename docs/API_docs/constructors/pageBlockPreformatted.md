---
title: pageBlockPreformatted
description: Preformatted text paragraph
---
## Constructor: pageBlockPreformatted  
[Back to constructors index](index.md)



Preformatted text paragraph

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[RichText](../types/RichText.md) | Yes|Paragraph text|
|language|[string](../types/string.md) | Yes|Programming language for which the text should be formatted|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockPreformatted = ['_' => 'pageBlockPreformatted', 'text' => RichText, 'language' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockPreformatted", "text": RichText, "language": "string"}
```


Or, if you're into Lua:  


```
pageBlockPreformatted={_='pageBlockPreformatted', text=RichText, language='string'}

```


