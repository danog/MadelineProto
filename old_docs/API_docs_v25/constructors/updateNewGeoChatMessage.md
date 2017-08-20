---
title: updateNewGeoChatMessage
description: updateNewGeoChatMessage attributes, type and example
---
## Constructor: updateNewGeoChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[GeoChatMessage](../types/GeoChatMessage.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewGeoChatMessage = ['_' => 'updateNewGeoChatMessage', 'message' => GeoChatMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewGeoChatMessage", "message": GeoChatMessage}
```


Or, if you're into Lua:  


```
updateNewGeoChatMessage={_='updateNewGeoChatMessage', message=GeoChatMessage}

```


