---
title: keyboardButtonSwitchInline
description: keyboardButtonSwitchInline attributes, type and example
---
## Constructor: keyboardButtonSwitchInline  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|same\_peer|[Bool](../types/Bool.md) | Optional|
|text|[string](../types/string.md) | Yes|
|query|[string](../types/string.md) | Yes|



### Type: [KeyboardButton](../types/KeyboardButton.md)


### Example:

```
$keyboardButtonSwitchInline = ['_' => 'keyboardButtonSwitchInline', 'same_peer' => Bool, 'text' => 'string', 'query' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "keyboardButtonSwitchInline", "same_peer": Bool, "text": "string", "query": "string"}
```


Or, if you're into Lua:  


```
keyboardButtonSwitchInline={_='keyboardButtonSwitchInline', same_peer=Bool, text='string', query='string'}

```


