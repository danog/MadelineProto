---
title: replyKeyboardHide
description: replyKeyboardHide attributes, type and example
---
## Constructor: replyKeyboardHide  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|selective|[Bool](../types/Bool.md) | Optional|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyKeyboardHide = ['_' => 'replyKeyboardHide', 'selective' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyKeyboardHide", "selective": Bool}
```


Or, if you're into Lua:  


```
replyKeyboardHide={_='replyKeyboardHide', selective=Bool}

```


