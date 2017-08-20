---
title: replyKeyboardMarkup
description: replyKeyboardMarkup attributes, type and example
---
## Constructor: replyKeyboardMarkup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Yes|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyKeyboardMarkup = ['_' => 'replyKeyboardMarkup', 'rows' => [KeyboardButtonRow]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyKeyboardMarkup", "rows": [KeyboardButtonRow]}
```


Or, if you're into Lua:  


```
replyKeyboardMarkup={_='replyKeyboardMarkup', rows={KeyboardButtonRow}}

```


