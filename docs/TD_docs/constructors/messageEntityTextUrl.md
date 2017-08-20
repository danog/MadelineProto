---
title: messageEntityTextUrl
description: Text description showed instead of the url
---
## Constructor: messageEntityTextUrl  
[Back to constructors index](index.md)



Text description showed instead of the url

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|
|url|[string](../types/string.md) | Yes|Url to be opened after link will be clicked|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityTextUrl = ['_' => 'messageEntityTextUrl', 'offset' => int, 'length' => int, 'url' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityTextUrl", "offset": int, "length": int, "url": "string"}
```


Or, if you're into Lua:  


```
messageEntityTextUrl={_='messageEntityTextUrl', offset=int, length=int, url='string'}

```


