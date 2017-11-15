---
title: inputPrivacyValueDisallowUsers
description: inputPrivacyValueDisallowUsers attributes, type and example
---
## Constructor: inputPrivacyValueDisallowUsers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|users|Array of [InputUser](../types/InputUser.md) | Yes|



### Type: [InputPrivacyRule](../types/InputPrivacyRule.md)


### Example:

```
$inputPrivacyValueDisallowUsers = ['_' => 'inputPrivacyValueDisallowUsers', 'users' => [InputUser]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPrivacyValueDisallowUsers", "users": [InputUser]}
```


Or, if you're into Lua:  


```
inputPrivacyValueDisallowUsers={_='inputPrivacyValueDisallowUsers', users={InputUser}}

```


