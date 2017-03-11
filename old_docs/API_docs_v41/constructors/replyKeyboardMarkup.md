---
title: replyKeyboardMarkup
description: replyKeyboardMarkup attributes, type and example
---
## Constructor: replyKeyboardMarkup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|resize|[Bool](../types/Bool.md) | Optional|
|single\_use|[Bool](../types/Bool.md) | Optional|
|selective|[Bool](../types/Bool.md) | Optional|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Yes|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyKeyboardMarkup = ['_' => 'replyKeyboardMarkup', 'resize' => true, 'single_use' => true, 'selective' => true, 'rows' => [KeyboardButtonRow], ];
```  

Or, if you're into Lua:  


```
replyKeyboardMarkup={_='replyKeyboardMarkup', resize=true, single_use=true, selective=true, rows={KeyboardButtonRow}, }

```


