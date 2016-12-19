## Constructor: updateReadHistoryOutbox  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Required|
|max\_id|[int](../types/int.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updateReadHistoryOutbox = ['peer' => Peer, 'max_id' => int, 'pts' => int, 'pts_count' => int, ];
```