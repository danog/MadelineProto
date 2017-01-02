---
title: account_noPassword
description: account_noPassword attributes, type and example
---
## Constructor: account\_noPassword  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|new\_salt|[bytes](../types/bytes.md) | Required|
|email\_unconfirmed\_pattern|[string](../types/string.md) | Required|



### Type: [account\_Password](../types/account_Password.md)


### Example:

```
$account_noPassword = ['_' => 'account_noPassword', 'new_salt' => bytes, 'email_unconfirmed_pattern' => string, ];
```