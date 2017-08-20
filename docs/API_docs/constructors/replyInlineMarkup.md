---
title: replyInlineMarkup
description: replyInlineMarkup attributes, type and example
---
## Constructor: replyInlineMarkup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Yes|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [KeyboardButtonRow]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyInlineMarkup", "rows": [KeyboardButtonRow]}
```


Or, if you're into Lua:  


```
replyInlineMarkup={_='replyInlineMarkup', rows={KeyboardButtonRow}}

```


