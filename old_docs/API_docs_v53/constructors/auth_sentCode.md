---
title: auth.sentCode
description: auth_sentCode attributes, type and example
---
## Constructor: auth.sentCode  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_registered|[Bool](../types/Bool.md) | Optional|
|type|[auth\_SentCodeType](../types/auth_SentCodeType.md) | Required|
|phone\_code\_hash|[string](../types/string.md) | Required|
|next\_type|[auth\_CodeType](../types/auth_CodeType.md) | Optional|
|timeout|[int](../types/int.md) | Optional|



### Type: [auth\_SentCode](../types/auth_SentCode.md)


### Example:

```
$auth_sentCode = ['_' => 'auth.sentCode', 'phone_registered' => true, 'type' => auth.SentCodeType, 'phone_code_hash' => string, 'next_type' => auth.CodeType, 'timeout' => int, ];
```  

