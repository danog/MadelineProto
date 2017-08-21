---
title: updates.difference
description: updates_difference attributes, type and example
---
## Constructor: updates.difference  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|new\_messages|Array of [Message](../types/Message.md) | Yes|
|new\_encrypted\_messages|Array of [EncryptedMessage](../types/EncryptedMessage.md) | Yes|
|other\_updates|Array of [Update](../types/Update.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|state|[updates\_State](../types/updates_State.md) | Yes|



### Type: [updates\_Difference](../types/updates_Difference.md)


### Example:

```
$updates_difference = ['_' => 'updates.difference', 'new_messages' => [Message], 'new_encrypted_messages' => [EncryptedMessage], 'other_updates' => [Update], 'chats' => [Chat], 'users' => [User], 'state' => updates_State];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.difference", "new_messages": [Message], "new_encrypted_messages": [EncryptedMessage], "other_updates": [Update], "chats": [Chat], "users": [User], "state": updates_State}
```


Or, if you're into Lua:  


```
updates_difference={_='updates.difference', new_messages={Message}, new_encrypted_messages={EncryptedMessage}, other_updates={Update}, chats={Chat}, users={User}, state=updates_State}

```


