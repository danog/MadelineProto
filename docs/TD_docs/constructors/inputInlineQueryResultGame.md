---
title: inputInlineQueryResultGame
description: Represents a game
---
## Constructor: inputInlineQueryResultGame  
[Back to constructors index](index.md)



Represents a game

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|game\_short\_name|[string](../types/string.md) | Yes|Game short name|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Yes|Message reply markup, should be of type replyMarkupInlineKeyboard or null|



### Type: [InputInlineQueryResult](../types/InputInlineQueryResult.md)


### Example:

```
$inputInlineQueryResultGame = ['_' => 'inputInlineQueryResultGame', 'id' => 'string', 'game_short_name' => 'string', 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputInlineQueryResultGame", "id": "string", "game_short_name": "string", "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
inputInlineQueryResultGame={_='inputInlineQueryResultGame', id='string', game_short_name='string', reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


