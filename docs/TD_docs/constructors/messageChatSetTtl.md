---
title: messageChatSetTtl
description: Messages ttl setting in secret chat has changed
---
## Constructor: messageChatSetTtl  
[Back to constructors index](index.md)



Messages ttl setting in secret chat has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|ttl|[int](../types/int.md) | Yes|New ttl|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatSetTtl = ['_' => 'messageChatSetTtl', 'ttl' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatSetTtl", "ttl": int}
```


Or, if you're into Lua:  


```
messageChatSetTtl={_='messageChatSetTtl', ttl=int}

```


