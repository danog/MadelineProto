---
title: updateReadChannelInbox
---
## Constructor: updateReadChannelInbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|max\_id|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadChannelInbox = ['_' => updateReadChannelInbox', 'channel_id' => int, 'max_id' => int, ];
```