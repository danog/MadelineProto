---
title: privacyValueDisallowUsers
description: privacyValueDisallowUsers attributes, type and example
---
## Constructor: privacyValueDisallowUsers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|users|Array of [int](../types/int.md) | Yes|



### Type: [PrivacyRule](../types/PrivacyRule.md)


### Example:

```
$privacyValueDisallowUsers = ['_' => 'privacyValueDisallowUsers', 'users' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privacyValueDisallowUsers", "users": [int]}
```


Or, if you're into Lua:  


```
privacyValueDisallowUsers={_='privacyValueDisallowUsers', users={int}}

```


