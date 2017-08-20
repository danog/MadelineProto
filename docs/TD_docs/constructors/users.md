---
title: users
description: Contains list of users
---
## Constructor: users  
[Back to constructors index](index.md)



Contains list of users

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|total\_count|[int](../types/int.md) | Yes|Approximate total count of found users|
|users|Array of [user](../constructors/user.md) | Yes|List of users|



### Type: [Users](../types/Users.md)


### Example:

```
$users = ['_' => 'users', 'total_count' => int, 'users' => [user]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "users", "total_count": int, "users": [user]}
```


Or, if you're into Lua:  


```
users={_='users', total_count=int, users={user}}

```


