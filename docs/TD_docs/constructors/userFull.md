---
title: userFull
description: Gives full information about a user (except full list of profile photos)
---
## Constructor: userFull  
[Back to constructors index](index.md)



Gives full information about a user (except full list of profile photos)

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_blocked|[Bool](../types/Bool.md) | Yes|Is user blacklisted by the current user|
|can\_be\_called|[Bool](../types/Bool.md) | Yes|True, if the user can be called|
|has\_private\_calls|[Bool](../types/Bool.md) | Yes|True, if the user can't be called only because of his privacy settings|
|about|[string](../types/string.md) | Yes|Short user bio or bot share text|
|common\_chat\_count|[int](../types/int.md) | Yes|Number of common chats between the user and current user, 0 for the current user|
|bot\_info|[botInfo](../types/botInfo.md) | Yes|Information about bot if user is a bot, nullable|



### Type: [UserFull](../types/UserFull.md)


