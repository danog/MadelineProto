---
title: help.recentMeUrls
description: help_recentMeUrls attributes, type and example
---
## Constructor: help.recentMeUrls  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|urls|Array of [RecentMeUrl](../types/RecentMeUrl.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [help\_RecentMeUrls](../types/help_RecentMeUrls.md)


### Example:

```
$help_recentMeUrls = ['_' => 'help.recentMeUrls', 'urls' => [RecentMeUrl], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "help.recentMeUrls", "urls": [RecentMeUrl], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
help_recentMeUrls={_='help.recentMeUrls', urls={RecentMeUrl}, chats={Chat}, users={User}}

```


