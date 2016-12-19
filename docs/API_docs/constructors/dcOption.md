## Constructor: dcOption  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|ipv6|[Bool](../types/Bool.md) | Optional|
|media\_only|[Bool](../types/Bool.md) | Optional|
|tcpo\_only|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|ip\_address|[string](../types/string.md) | Required|
|port|[int](../types/int.md) | Required|



### Type: [DcOption](../types/DcOption.md)


### Example:

```
$dcOption = ['_' => dcOption', 'ipv6' => true, 'media_only' => true, 'tcpo_only' => true, 'id' => int, 'ip_address' => string, 'port' => int, ];
```