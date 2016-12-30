---
title: updatePrivacy
description: updatePrivacy attributes, type and example
---
## Constructor: updatePrivacy  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|key|[PrivacyKey](../types/PrivacyKey.md) | Required|
|rules|Array of [PrivacyRule](../types/PrivacyRule.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updatePrivacy = ['_' => 'updatePrivacy', 'key' => PrivacyKey, 'rules' => [Vector t], ];
```