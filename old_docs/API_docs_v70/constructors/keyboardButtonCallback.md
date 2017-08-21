---
title: keyboardButtonCallback
description: keyboardButtonCallback attributes, type and example
---
## Constructor: keyboardButtonCallback  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|text|[string](../types/string.md) | Yes|
|data|[bytes](../types/bytes.md) | Yes|



### Type: [KeyboardButton](../types/KeyboardButton.md)


### Example:

```
$keyboardButtonCallback = ['_' => 'keyboardButtonCallback', 'text' => 'string', 'data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "keyboardButtonCallback", "text": "string", "data": "bytes"}
```


Or, if you're into Lua:  


```
keyboardButtonCallback={_='keyboardButtonCallback', text='string', data='bytes'}

```


