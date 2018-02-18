---
title: channelAdminRights
description: channelAdminRights attributes, type and example
---
## Constructor: channelAdminRights  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|change\_info|[Bool](../types/Bool.md) | Optional|
|post\_messages|[Bool](../types/Bool.md) | Optional|
|edit\_messages|[Bool](../types/Bool.md) | Optional|
|delete\_messages|[Bool](../types/Bool.md) | Optional|
|ban\_users|[Bool](../types/Bool.md) | Optional|
|invite\_users|[Bool](../types/Bool.md) | Optional|
|invite\_link|[Bool](../types/Bool.md) | Optional|
|pin\_messages|[Bool](../types/Bool.md) | Optional|
|add\_admins|[Bool](../types/Bool.md) | Optional|



### Type: [ChannelAdminRights](../types/ChannelAdminRights.md)


### Example:

```
$channelAdminRights = ['_' => 'channelAdminRights', 'change_info' => Bool, 'post_messages' => Bool, 'edit_messages' => Bool, 'delete_messages' => Bool, 'ban_users' => Bool, 'invite_users' => Bool, 'invite_link' => Bool, 'pin_messages' => Bool, 'add_admins' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminRights", "change_info": Bool, "post_messages": Bool, "edit_messages": Bool, "delete_messages": Bool, "ban_users": Bool, "invite_users": Bool, "invite_link": Bool, "pin_messages": Bool, "add_admins": Bool}
```


Or, if you're into Lua:  


```
channelAdminRights={_='channelAdminRights', change_info=Bool, post_messages=Bool, edit_messages=Bool, delete_messages=Bool, ban_users=Bool, invite_users=Bool, invite_link=Bool, pin_messages=Bool, add_admins=Bool}

```


