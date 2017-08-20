---
title: phoneCall
description: phoneCall attributes, type and example
---
## Constructor: phoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participant\_id|[int](../types/int.md) | Yes|
|g\_a\_or\_b|[bytes](../types/bytes.md) | Yes|
|key\_fingerprint|[long](../types/long.md) | Yes|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Yes|
|connection|[PhoneConnection](../types/PhoneConnection.md) | Yes|
|alternative\_connections|Array of [PhoneConnection](../types/PhoneConnection.md) | Yes|
|start\_date|[int](../types/int.md) | Yes|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCall = ['_' => 'phoneCall', 'id' => long, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a_or_b' => 'bytes', 'key_fingerprint' => long, 'protocol' => PhoneCallProtocol, 'connection' => PhoneConnection, 'alternative_connections' => [PhoneConnection], 'start_date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "phoneCall", "id": long, "access_hash": long, "date": int, "admin_id": int, "participant_id": int, "g_a_or_b": "bytes", "key_fingerprint": long, "protocol": PhoneCallProtocol, "connection": PhoneConnection, "alternative_connections": [PhoneConnection], "start_date": int}
```


Or, if you're into Lua:  


```
phoneCall={_='phoneCall', id=long, access_hash=long, date=int, admin_id=int, participant_id=int, g_a_or_b='bytes', key_fingerprint=long, protocol=PhoneCallProtocol, connection=PhoneConnection, alternative_connections={PhoneConnection}, start_date=int}

```


