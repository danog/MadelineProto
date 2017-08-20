---
title: privacyRuleAllowUsers
description: Rule to allow specified users
---
## Constructor: privacyRuleAllowUsers  
[Back to constructors index](index.md)



Rule to allow specified users

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_ids|Array of [int](../constructors/int.md) | Yes|User identifiers|



### Type: [PrivacyRule](../types/PrivacyRule.md)


### Example:

```
$privacyRuleAllowUsers = ['_' => 'privacyRuleAllowUsers', 'user_ids' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privacyRuleAllowUsers", "user_ids": [int]}
```


Or, if you're into Lua:  


```
privacyRuleAllowUsers={_='privacyRuleAllowUsers', user_ids={int}}

```


