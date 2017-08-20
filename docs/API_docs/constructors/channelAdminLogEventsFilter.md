---
title: channelAdminLogEventsFilter
description: channelAdminLogEventsFilter attributes, type and example
---
## Constructor: channelAdminLogEventsFilter  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|join|[Bool](../types/Bool.md) | Optional|
|leave|[Bool](../types/Bool.md) | Optional|
|invite|[Bool](../types/Bool.md) | Optional|
|ban|[Bool](../types/Bool.md) | Optional|
|unban|[Bool](../types/Bool.md) | Optional|
|kick|[Bool](../types/Bool.md) | Optional|
|unkick|[Bool](../types/Bool.md) | Optional|
|promote|[Bool](../types/Bool.md) | Optional|
|demote|[Bool](../types/Bool.md) | Optional|
|info|[Bool](../types/Bool.md) | Optional|
|settings|[Bool](../types/Bool.md) | Optional|
|pinned|[Bool](../types/Bool.md) | Optional|
|edit|[Bool](../types/Bool.md) | Optional|
|delete|[Bool](../types/Bool.md) | Optional|



### Type: [ChannelAdminLogEventsFilter](../types/ChannelAdminLogEventsFilter.md)


### Example:

```
$channelAdminLogEventsFilter = ['_' => 'channelAdminLogEventsFilter', 'join' => Bool, 'leave' => Bool, 'invite' => Bool, 'ban' => Bool, 'unban' => Bool, 'kick' => Bool, 'unkick' => Bool, 'promote' => Bool, 'demote' => Bool, 'info' => Bool, 'settings' => Bool, 'pinned' => Bool, 'edit' => Bool, 'delete' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventsFilter", "join": Bool, "leave": Bool, "invite": Bool, "ban": Bool, "unban": Bool, "kick": Bool, "unkick": Bool, "promote": Bool, "demote": Bool, "info": Bool, "settings": Bool, "pinned": Bool, "edit": Bool, "delete": Bool}
```


Or, if you're into Lua:  


```
channelAdminLogEventsFilter={_='channelAdminLogEventsFilter', join=Bool, leave=Bool, invite=Bool, ban=Bool, unban=Bool, kick=Bool, unkick=Bool, promote=Bool, demote=Bool, info=Bool, settings=Bool, pinned=Bool, edit=Bool, delete=Bool}

```


