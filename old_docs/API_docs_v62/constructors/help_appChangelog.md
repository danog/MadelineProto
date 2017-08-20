---
title: help.appChangelog
description: help_appChangelog attributes, type and example
---
## Constructor: help.appChangelog  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Yes|



### Type: [help\_AppChangelog](../types/help_AppChangelog.md)


### Example:

```
$help_appChangelog = ['_' => 'help.appChangelog', 'message' => 'string', 'media' => MessageMedia, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "help.appChangelog", "message": "string", "media": MessageMedia, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
help_appChangelog={_='help.appChangelog', message='string', media=MessageMedia, entities={MessageEntity}}

```


