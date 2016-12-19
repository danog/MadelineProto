## Constructor: updatePrivacy  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|key|[PrivacyKey](../types/PrivacyKey.md) | Required|
|rules|Array of [PrivacyRule](../types/PrivacyRule.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updatePrivacy = ['key' => PrivacyKey, 'rules' => [PrivacyRule], ];
```