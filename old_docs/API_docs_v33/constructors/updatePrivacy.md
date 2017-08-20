---
title: updatePrivacy
description: updatePrivacy attributes, type and example
---
## Constructor: updatePrivacy  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|key|[PrivacyKey](../types/PrivacyKey.md) | Yes|
|rules|Array of [PrivacyRule](../types/PrivacyRule.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updatePrivacy = ['_' => 'updatePrivacy', 'key' => PrivacyKey, 'rules' => [PrivacyRule]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updatePrivacy", "key": PrivacyKey, "rules": [PrivacyRule]}
```


Or, if you're into Lua:  


```
updatePrivacy={_='updatePrivacy', key=PrivacyKey, rules={PrivacyRule}}

```


