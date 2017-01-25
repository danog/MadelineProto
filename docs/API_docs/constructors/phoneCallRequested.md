---
title: phoneCallRequested
description: phoneCallRequested attributes, type and example
---
## Constructor: phoneCallRequested  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|
|g\_a|[bytes](../types/bytes.md) | Required|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Required|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCallRequested = ['_' => 'phoneCallRequested', 'id' => long, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a' => bytes, 'protocol' => PhoneCallProtocol, ];
```  

