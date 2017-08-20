---
title: updateSecretChat
description: Some data about a secret chat has been changed
---
## Constructor: updateSecretChat  
[Back to constructors index](index.md)



Some data about a secret chat has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|secret\_chat|[secretChat](../types/secretChat.md) | Yes|New data about the secret chat|



### Type: [Update](../types/Update.md)


### Example:

```
$updateSecretChat = ['_' => 'updateSecretChat', 'secret_chat' => secretChat];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateSecretChat", "secret_chat": secretChat}
```


Or, if you're into Lua:  


```
updateSecretChat={_='updateSecretChat', secret_chat=secretChat}

```


