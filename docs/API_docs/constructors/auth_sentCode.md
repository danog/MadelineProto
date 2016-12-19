## Constructor: auth\_sentCode  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_registered|[Bool](../types/Bool.md) | Optional|
|type|[auth\_SentCodeType](../types/auth\_SentCodeType.md) | Required|
|phone\_code\_hash|[string](../types/string.md) | Required|
|next\_type|[auth\_CodeType](../types/auth\_CodeType.md) | Optional|
|timeout|[int](../types/int.md) | Optional|


### Type: [auth\_SentCode](../types/auth\_SentCode.md)

### Example:


```
$auth_sentCode = ['phone_registered' => Bool, 'type' => auth_SentCodeType, 'phone_code_hash' => string, 'next_type' => auth_CodeType, 'timeout' => int, ];
```