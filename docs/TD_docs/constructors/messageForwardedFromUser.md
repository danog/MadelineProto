---
title: messageForwardedFromUser
description: Message is originally written by known user
---
## Constructor: messageForwardedFromUser  
[Back to constructors index](index.md)



Message is originally written by known user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of a user, who originally sent this message|
|date|[int](../types/int.md) | Yes|Date when message was originally sent|



### Type: [MessageForwardInfo](../types/MessageForwardInfo.md)


### Example:

```
$messageForwardedFromUser = ['_' => 'messageForwardedFromUser', 'sender_user_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageForwardedFromUser", "sender_user_id": int, "date": int}
```


Or, if you're into Lua:  


```
messageForwardedFromUser={_='messageForwardedFromUser', sender_user_id=int, date=int}

```


