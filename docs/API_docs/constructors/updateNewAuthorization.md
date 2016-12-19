## Constructor: updateNewAuthorization  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|auth\_key\_id|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|device|[string](../types/string.md) | Required|
|location|[string](../types/string.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewAuthorization = ['_' => updateNewAuthorization', 'auth_key_id' => long, 'date' => int, 'device' => string, 'location' => string, ];
```