## Constructor: updateDeleteChannelMessages  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|messages|Array of [int](../types/int.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updateDeleteChannelMessages = ['channel_id' => int, 'messages' => [int], 'pts' => int, 'pts_count' => int, ];
```