---
title: phoneCallDiscarded
description: phoneCallDiscarded attributes, type and example
---
## Constructor: phoneCallDiscarded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Optional|
|duration|[int](../types/int.md) | Optional|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCallDiscarded = ['_' => 'phoneCallDiscarded', 'id' => long, 'reason' => PhoneCallDiscardReason, 'duration' => int, ];
```  

