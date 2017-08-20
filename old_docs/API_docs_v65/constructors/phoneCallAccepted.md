---
title: phoneCallAccepted
description: phoneCallAccepted attributes, type and example
---
## Constructor: phoneCallAccepted  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participant\_id|[int](../types/int.md) | Yes|
|g\_b|[bytes](../types/bytes.md) | Yes|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Yes|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCallAccepted = ['_' => 'phoneCallAccepted', 'id' => long, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_b' => 'bytes', 'protocol' => PhoneCallProtocol];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "phoneCallAccepted", "id": long, "access_hash": long, "date": int, "admin_id": int, "participant_id": int, "g_b": "bytes", "protocol": PhoneCallProtocol}
```


Or, if you're into Lua:  


```
phoneCallAccepted={_='phoneCallAccepted', id=long, access_hash=long, date=int, admin_id=int, participant_id=int, g_b='bytes', protocol=PhoneCallProtocol}

```


