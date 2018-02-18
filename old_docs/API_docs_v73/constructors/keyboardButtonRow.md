---
title: keyboardButtonRow
description: keyboardButtonRow attributes, type and example
---
## Constructor: keyboardButtonRow  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|buttons|Array of [KeyboardButton](../types/KeyboardButton.md) | Yes|



### Type: [KeyboardButtonRow](../types/KeyboardButtonRow.md)


### Example:

```
$keyboardButtonRow = ['_' => 'keyboardButtonRow', 'buttons' => [KeyboardButton]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "keyboardButtonRow", "buttons": [KeyboardButton]}
```


Or, if you're into Lua:  


```
keyboardButtonRow={_='keyboardButtonRow', buttons={KeyboardButton}}

```


