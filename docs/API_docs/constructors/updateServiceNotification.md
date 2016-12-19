## Constructor: updateServiceNotification  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|type|[string](../types/string.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|
|popup|[Bool](../types/Bool.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updateServiceNotification = ['type' => string, 'message' => string, 'media' => MessageMedia, 'popup' => Bool, ];
```