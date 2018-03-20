---
title: inputPrivacyValueAllowUsers
description: inputPrivacyValueAllowUsers attributes, type and example
---
## Constructor: inputPrivacyValueAllowUsers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|users|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|



### Type: [InputPrivacyRule](../types/InputPrivacyRule.md)


### Example:

```
$inputPrivacyValueAllowUsers = ['_' => 'inputPrivacyValueAllowUsers', 'users' => [InputUser, InputUser]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPrivacyValueAllowUsers", "users": [InputUser]}
```


Or, if you're into Lua:  


```
inputPrivacyValueAllowUsers={_='inputPrivacyValueAllowUsers', users={InputUser}}

```


