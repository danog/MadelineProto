---
title: replyKeyboardMarkup
description: replyKeyboardMarkup attributes, type and example
---
## Constructor: replyKeyboardMarkup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|resize|[Bool](../types/Bool.md) | Optional|
|single\_use|[Bool](../types/Bool.md) | Optional|
|selective|[Bool](../types/Bool.md) | Optional|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Yes|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyKeyboardMarkup = ['_' => 'replyKeyboardMarkup', 'resize' => Bool, 'single_use' => Bool, 'selective' => Bool, 'rows' => [KeyboardButtonRow]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyKeyboardMarkup", "resize": Bool, "single_use": Bool, "selective": Bool, "rows": [KeyboardButtonRow]}
```


Or, if you're into Lua:  


```
replyKeyboardMarkup={_='replyKeyboardMarkup', resize=Bool, single_use=Bool, selective=Bool, rows={KeyboardButtonRow}}

```


