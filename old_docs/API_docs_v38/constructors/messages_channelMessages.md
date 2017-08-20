---
title: messages.channelMessages
description: messages_channelMessages attributes, type and example
---
## Constructor: messages.channelMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pts|[int](../types/int.md) | Yes|
|count|[int](../types/int.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|collapsed|Array of [MessageGroup](../types/MessageGroup.md) | Optional|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_Messages](../types/messages_Messages.md)


### Example:

```
$messages_channelMessages = ['_' => 'messages.channelMessages', 'pts' => int, 'count' => int, 'messages' => [Message], 'collapsed' => [MessageGroup], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.channelMessages", "pts": int, "count": int, "messages": [Message], "collapsed": [MessageGroup], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
messages_channelMessages={_='messages.channelMessages', pts=int, count=int, messages={Message}, collapsed={MessageGroup}, chats={Chat}, users={User}}

```


