---
title: privacyRules
description: List of privacy rules. Rules are matched in the specified order. First matched rule defines privacy setting for a given user. If no rule matches action is not allowed
---
## Constructor: privacyRules  
[Back to constructors index](index.md)



List of privacy rules. Rules are matched in the specified order. First matched rule defines privacy setting for a given user. If no rule matches action is not allowed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|rules|Array of [PrivacyRule](../constructors/PrivacyRule.md) | Yes|List of rules|



### Type: [PrivacyRules](../types/PrivacyRules.md)


### Example:

```
$privacyRules = ['_' => 'privacyRules', 'rules' => [PrivacyRule]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "privacyRules", "rules": [PrivacyRule]}
```


Or, if you're into Lua:  


```
privacyRules={_='privacyRules', rules={PrivacyRule}}

```


