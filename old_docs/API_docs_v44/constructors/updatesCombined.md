---
title: updatesCombined
description: updatesCombined attributes, type and example
---
## Constructor: updatesCombined  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|updates|Array of [Update](../types/Update.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|date|[int](../types/int.md) | Yes|
|seq\_start|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updatesCombined = ['_' => 'updatesCombined', 'updates' => [Update], 'users' => [User], 'chats' => [Chat], 'date' => int, 'seq_start' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updatesCombined", "updates": [Update], "users": [User], "chats": [Chat], "date": int, "seq_start": int, "seq": int}
```


Or, if you're into Lua:  


```
updatesCombined={_='updatesCombined', updates={Update}, users={User}, chats={Chat}, date=int, seq_start=int, seq=int}

```


