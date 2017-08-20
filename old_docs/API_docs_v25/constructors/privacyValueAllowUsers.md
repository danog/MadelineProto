---
title: privacyValueAllowUsers
description: privacyValueAllowUsers attributes, type and example
---
## Constructor: privacyValueAllowUsers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|users|Array of [int](../types/int.md) | Yes|



### Type: [PrivacyRule](../types/PrivacyRule.md)


### Example:

```
$privacyValueAllowUsers = ['_' => 'privacyValueAllowUsers', 'users' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privacyValueAllowUsers", "users": [int]}
```


Or, if you're into Lua:  


```
privacyValueAllowUsers={_='privacyValueAllowUsers', users={int}}

```


