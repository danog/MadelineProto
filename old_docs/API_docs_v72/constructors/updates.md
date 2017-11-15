---
title: updates
description: updates attributes, type and example
---
## Constructor: updates  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|updates|Array of [Update](../types/Update.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|date|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updates = ['_' => 'updates', 'updates' => [Update], 'users' => [User], 'chats' => [Chat], 'date' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates", "updates": [Update], "users": [User], "chats": [Chat], "date": int, "seq": int}
```


Or, if you're into Lua:  


```
updates={_='updates', updates={Update}, users={User}, chats={Chat}, date=int, seq=int}

```


