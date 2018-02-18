---
title: inputUser
description: inputUser attributes, type and example
---
## Constructor: inputUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUser = ['_' => 'inputUser', 'user_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputUser", "user_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputUser={_='inputUser', user_id=int, access_hash=long}

```


