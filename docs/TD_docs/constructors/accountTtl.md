---
title: accountTtl
description: Contains infotmation about period of inactivity, after which the account of currently logged in user will be automatically deleted
---
## Constructor: accountTtl  
[Back to constructors index](index.md)



Contains infotmation about period of inactivity, after which the account of currently logged in user will be automatically deleted

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|days|[int](../types/int.md) | Yes|Number of days of inactivity before account deletion, should be from 30 up to 366|



### Type: [AccountTtl](../types/AccountTtl.md)


### Example:

```
$accountTtl = ['_' => 'accountTtl', 'days' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "accountTtl", "days": int}
```


Or, if you're into Lua:  


```
accountTtl={_='accountTtl', days=int}

```


