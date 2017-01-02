---
title: account_sentChangePhoneCode
description: account_sentChangePhoneCode attributes, type and example
---
## Constructor: account\_sentChangePhoneCode  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_code\_hash|[string](../types/string.md) | Required|
|send\_call\_timeout|[int](../types/int.md) | Required|



### Type: [account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)


### Example:

```
$account_sentChangePhoneCode = ['_' => 'account_sentChangePhoneCode', 'phone_code_hash' => string, 'send_call_timeout' => int, ];
```