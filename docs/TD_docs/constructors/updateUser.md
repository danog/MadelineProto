---
title: updateUser
description: Some data about a user has been changed
---
## Constructor: updateUser  
[Back to constructors index](index.md)



Some data about a user has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user|[user](../types/user.md) | Yes|New data about the user|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUser = ['_' => 'updateUser', 'user' => user];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUser", "user": user}
```


Or, if you're into Lua:  


```
updateUser={_='updateUser', user=user}

```


