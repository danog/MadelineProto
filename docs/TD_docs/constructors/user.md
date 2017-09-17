---
title: user
description: Represents user
---
## Constructor: user  
[Back to constructors index](index.md)



Represents user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|User identifier|
|first\_name|[string](../types/string.md) | Yes|User first name|
|last\_name|[string](../types/string.md) | Yes|User last name|
|username|[string](../types/string.md) | Yes|User username|
|phone\_number|[string](../types/string.md) | Yes|User's phone number|
|status|[UserStatus](../types/UserStatus.md) | Yes|User's online status|
|profile\_photo|[profilePhoto](../types/profilePhoto.md) | Yes|User profile photo, nullable|
|my\_link|[LinkState](../types/LinkState.md) | Yes|Relationships from me to other user|
|foreign\_link|[LinkState](../types/LinkState.md) | Yes|Relationships from other user to me|
|is\_verified|[Bool](../types/Bool.md) | Yes|True, if user is verified|
|restriction\_reason|[string](../types/string.md) | Yes|If non-empty, contains the reason, why access to this user must be restricted. Format of the string is "{type}: {description}". {type} contains type of the restriction and at least one of the suffixes "-all", "-ios", "-android", "-wp", which describes platforms on which access should be restricted. For example, "terms-ios-android". {description} contains human-readable description of the restriction, which can be showed to the user|
|have\_access|[Bool](../types/Bool.md) | Yes|If false, the user is inaccessible and the only known information about it is inside this class. It can't be passed to any method except GetUser. Currently it can be false only for inaccessible authors of the channel posts|
|type|[UserType](../types/UserType.md) | Yes|Type of the user|
|language\_code|[string](../types/string.md) | Yes|Bots only. IETF language tag of users language|



### Type: [User](../types/User.md)


