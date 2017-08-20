---
title: privacyRuleDisallowUsers
description: Rule to disallow all specified users
---
## Constructor: privacyRuleDisallowUsers  
[Back to constructors index](index.md)



Rule to disallow all specified users

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_ids|Array of [int](../constructors/int.md) | Yes|User identifiers|



### Type: [PrivacyRule](../types/PrivacyRule.md)


### Example:

```
$privacyRuleDisallowUsers = ['_' => 'privacyRuleDisallowUsers', 'user_ids' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privacyRuleDisallowUsers", "user_ids": [int]}
```


Or, if you're into Lua:  


```
privacyRuleDisallowUsers={_='privacyRuleDisallowUsers', user_ids={int}}

```


