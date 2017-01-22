---
title: messages.dialogsSlice
description: messages_dialogsSlice attributes, type and example
---
## Constructor: messages.dialogsSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|dialogs|Array of [Dialog](../types/Dialog.md) | Required|
|messages|Array of [MTMessage](../types/MTMessage.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_Dialogs](../types/messages_Dialogs.md)


### Example:

```
$messages_dialogsSlice = ['_' => 'messages.dialogsSlice', 'count' => int, 'dialogs' => [Vector t], 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

