---
title: account_password
---
## Constructor: account\_password  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|current\_salt|[bytes](../types/bytes.md) | Required|
|new\_salt|[bytes](../types/bytes.md) | Required|
|hint|[string](../types/string.md) | Required|
|has\_recovery|[Bool](../types/Bool.md) | Required|
|email\_unconfirmed\_pattern|[string](../types/string.md) | Required|



### Type: [account\_Password](../types/account_Password.md)


### Example:

```
$account_password = ['_' => account_password', 'current_salt' => bytes, 'new_salt' => bytes, 'hint' => string, 'has_recovery' => Bool, 'email_unconfirmed_pattern' => string, ];
```