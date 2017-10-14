---
title: updateServiceNotification
description: updateServiceNotification attributes, type and example
---
## Constructor: updateServiceNotification  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|popup|[Bool](../types/Bool.md) | Optional|
|inbox\_date|[int](../types/int.md) | Optional|
|type|[string](../types/string.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateServiceNotification = ['_' => 'updateServiceNotification', 'popup' => Bool, 'inbox_date' => int, 'type' => 'string', 'message' => 'string', 'media' => MessageMedia, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateServiceNotification", "popup": Bool, "inbox_date": int, "type": "string", "message": "string", "media": MessageMedia, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateServiceNotification={_='updateServiceNotification', popup=Bool, inbox_date=int, type='string', message='string', media=MessageMedia, entities={MessageEntity}}

```


