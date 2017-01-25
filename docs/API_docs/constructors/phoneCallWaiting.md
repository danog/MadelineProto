---
title: phoneCallWaiting
description: phoneCallWaiting attributes, type and example
---
## Constructor: phoneCallWaiting  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Required|
|receive\_date|[int](../types/int.md) | Optional|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCallWaiting = ['_' => 'phoneCallWaiting', 'id' => long, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'protocol' => PhoneCallProtocol, 'receive_date' => int, ];
```  

