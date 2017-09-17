---
title: channel
description: Represents a channel with zero or more subscribers. There two different kinds of channels: supergroups and broadcast channels
---
## Constructor: channel  
[Back to constructors index](index.md)



Represents a channel with zero or more subscribers. There two different kinds of channels: supergroups and broadcast channels

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Channel identifier|
|username|[string](../types/string.md) | Yes|Channel username, empty for private channels|
|date|[int](../types/int.md) | Yes|Date when current user has joined the channel or date when channel was created, if user is not a member. Unix time|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|Status of the current user in the channel|
|anyone\_can\_invite|[Bool](../types/Bool.md) | Yes|True, if any member of the supergroup can invite other members. If the channel is not a supergroup, the field is meaningless|
|sign\_messages|[Bool](../types/Bool.md) | Yes|True, if messages sent to the channel should content information about the sender. If the channel is a supergroup, the field is meaningless|
|is\_supergroup|[Bool](../types/Bool.md) | Yes|True, if channel is a supergroup and is not a broadcast|
|is\_verified|[Bool](../types/Bool.md) | Yes|True, if the channel is verified|
|restriction\_reason|[string](../types/string.md) | Yes|If non-empty, contains the reason, why access to this channel must be restricted. Format of the string is "{type}: {description}". {type} contains type of the restriction and at least one of the suffixes "-all", "-ios", "-android", "-wp", which describes platforms on which access should be restricted. For example, "terms-ios-android". {description} contains human-readable description of the restriction, which can be showed to the user|



### Type: [Channel](../types/Channel.md)


