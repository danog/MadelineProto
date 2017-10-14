---
title: replyKeyboardForceReply
description: replyKeyboardForceReply attributes, type and example
---
## Constructor: replyKeyboardForceReply  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|single\_use|[Bool](../types/Bool.md) | Optional|
|selective|[Bool](../types/Bool.md) | Optional|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyKeyboardForceReply = ['_' => 'replyKeyboardForceReply', 'single_use' => Bool, 'selective' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyKeyboardForceReply", "single_use": Bool, "selective": Bool}
```


Or, if you're into Lua:  


```
replyKeyboardForceReply={_='replyKeyboardForceReply', single_use=Bool, selective=Bool}

```


