---
title: inlineKeyboardButtonTypeUrl
description: A button which opens the specified URL
---
## Constructor: inlineKeyboardButtonTypeUrl  
[Back to constructors index](index.md)



A button which opens the specified URL

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|url|[string](../types/string.md) | Yes|URL to open|



### Type: [InlineKeyboardButtonType](../types/InlineKeyboardButtonType.md)


### Example:

```
$inlineKeyboardButtonTypeUrl = ['_' => 'inlineKeyboardButtonTypeUrl', 'url' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineKeyboardButtonTypeUrl", "url": "string"}
```


Or, if you're into Lua:  


```
inlineKeyboardButtonTypeUrl={_='inlineKeyboardButtonTypeUrl', url='string'}

```


