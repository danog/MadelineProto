---
title: secretChatInfo
description: Secret chat with one user
---
## Constructor: secretChatInfo  
[Back to constructors index](index.md)



Secret chat with one user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|secret\_chat|[secretChat](../types/secretChat.md) | Yes|Information about the chat|



### Type: [ChatInfo](../types/ChatInfo.md)


### Example:

```
$secretChatInfo = ['_' => 'secretChatInfo', 'secret_chat' => secretChat];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "secretChatInfo", "secret_chat": secretChat}
```


Or, if you're into Lua:  


```
secretChatInfo={_='secretChatInfo', secret_chat=secretChat}

```


