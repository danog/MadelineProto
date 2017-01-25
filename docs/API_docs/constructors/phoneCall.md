---
title: phoneCall
description: phoneCall attributes, type and example
---
## Constructor: phoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|
|g\_a\_or\_b|[bytes](../types/bytes.md) | Required|
|key\_fingerprint|[long](../types/long.md) | Required|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Required|
|connection|[PhoneConnection](../types/PhoneConnection.md) | Required|
|alternative\_connections|Array of [PhoneConnection](../types/PhoneConnection.md) | Required|
|start\_date|[int](../types/int.md) | Required|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCall = ['_' => 'phoneCall', 'id' => long, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a_or_b' => bytes, 'key_fingerprint' => long, 'protocol' => PhoneCallProtocol, 'connection' => PhoneConnection, 'alternative_connections' => [Vector t], 'start_date' => int, ];
```  

