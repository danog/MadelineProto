---
title: messages.savedGifs
description: messages_savedGifs attributes, type and example
---
## Constructor: messages.savedGifs  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|
|gifs|Array of [Document](../types/Document.md) | Yes|



### Type: [messages\_SavedGifs](../types/messages_SavedGifs.md)


### Example:

```
$messages_savedGifs = ['_' => 'messages.savedGifs', 'hash' => int, 'gifs' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.savedGifs", "hash": int, "gifs": [Document]}
```


Or, if you're into Lua:  


```
messages_savedGifs={_='messages.savedGifs', hash=int, gifs={Document}}

```


