---
title: replyMarkupInlineKeyboard
description: Contains inline keyboard layout
---
## Constructor: replyMarkupInlineKeyboard  
[Back to constructors index](index.md)



Contains inline keyboard layout

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|rows|Array of [inlineKeyboardButton>](../constructors/inlineKeyboardButton>.md) | Yes|List of rows of inline keyboard buttons|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


### Example:

```
$replyMarkupInlineKeyboard = ['_' => 'replyMarkupInlineKeyboard', 'rows' => [inlineKeyboardButton>]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "replyMarkupInlineKeyboard", "rows": [inlineKeyboardButton>]}
```


Or, if you're into Lua:  


```
replyMarkupInlineKeyboard={_='replyMarkupInlineKeyboard', rows={inlineKeyboardButton>}}

```


