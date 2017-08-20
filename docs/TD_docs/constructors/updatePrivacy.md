---
title: updatePrivacy
description: Some privacy settings has changed
---
## Constructor: updatePrivacy  
[Back to constructors index](index.md)



Some privacy settings has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|key|[PrivacyKey](../types/PrivacyKey.md) | Yes|Privacy key|
|rules|[privacyRules](../types/privacyRules.md) | Yes|New privacy rules|



### Type: [Update](../types/Update.md)


### Example:

```
$updatePrivacy = ['_' => 'updatePrivacy', 'key' => PrivacyKey, 'rules' => privacyRules];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updatePrivacy", "key": PrivacyKey, "rules": privacyRules}
```


Or, if you're into Lua:  


```
updatePrivacy={_='updatePrivacy', key=PrivacyKey, rules=privacyRules}

```


