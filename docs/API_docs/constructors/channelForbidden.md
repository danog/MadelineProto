## Constructor: channelForbidden  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|broadcast|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|


### Type: [Chat](../types/Chat.md)

### Example:


```
$channelForbidden = ['broadcast' => Bool, 'megagroup' => Bool, 'id' => int, 'access_hash' => long, 'title' => string, ];
```