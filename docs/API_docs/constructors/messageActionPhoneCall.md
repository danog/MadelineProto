---
title: messageActionPhoneCall
description: messageActionPhoneCall attributes, type and example
---
## Constructor: messageActionPhoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|call\_id|[long](../types/long.md) | Required|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Optional|
|duration|[int](../types/int.md) | Optional|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionPhoneCall = ['_' => 'messageActionPhoneCall', 'call_id' => long, 'reason' => PhoneCallDiscardReason, 'duration' => int, ];
```  

