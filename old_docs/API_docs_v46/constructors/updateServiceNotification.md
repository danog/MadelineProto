---
title: updateServiceNotification
description: updateServiceNotification attributes, type and example
---
## Constructor: updateServiceNotification  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|type|[string](../types/string.md) | Yes|
|message\_text|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Optional|
|popup|[Bool](../types/Bool.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateServiceNotification = ['_' => 'updateServiceNotification', 'type' => 'string', 'message_text' => 'string', 'media' => MessageMedia, 'popup' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateServiceNotification", "type": "string", "message_text": "string", "media": MessageMedia, "popup": Bool}
```


Or, if you're into Lua:  


```
updateServiceNotification={_='updateServiceNotification', type='string', message_text='string', media=MessageMedia, popup=Bool}

```


