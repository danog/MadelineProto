## Constructor: account\_noPassword  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|new\_salt|[bytes](../types/bytes.md) | Required|
|email\_unconfirmed\_pattern|[string](../types/string.md) | Required|


### Type: [account\_Password](../types/account\_Password.md)

### Example:


```
$account_noPassword = ['new_salt' => bytes, 'email_unconfirmed_pattern' => string, ];
```