---
title: channels.adminLogResults
description: channels_adminLogResults attributes, type and example
---
## Constructor: channels.adminLogResults  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|events|Array of [ChannelAdminLogEvent](../types/ChannelAdminLogEvent.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [channels\_AdminLogResults](../types/channels_AdminLogResults.md)


### Example:

```
$channels_adminLogResults = ['_' => 'channels.adminLogResults', 'events' => [ChannelAdminLogEvent], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channels.adminLogResults", "events": [ChannelAdminLogEvent], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
channels_adminLogResults={_='channels.adminLogResults', events={ChannelAdminLogEvent}, chats={Chat}, users={User}}

```


