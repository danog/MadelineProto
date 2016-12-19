## Constructor: updateReadChannelOutbox  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|max\_id|[int](../types/int.md) | Required|
### Type: 

[Update](../types/Update.md)
### Example:

```
$updateReadChannelOutbox = ['_' => updateReadChannelOutbox', 'channel_id' => int, 'max_id' => int, ];
```