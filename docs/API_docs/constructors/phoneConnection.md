---
title: phoneConnection
description: phoneConnection attributes, type and example
---
## Constructor: phoneConnection  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|ip|[string](../types/string.md) | Required|
|ipv6|[string](../types/string.md) | Required|
|port|[int](../types/int.md) | Required|
|peer\_tag|[bytes](../types/bytes.md) | Required|



### Type: [PhoneConnection](../types/PhoneConnection.md)


### Example:

```
$phoneConnection = ['_' => 'phoneConnection', 'id' => long, 'ip' => string, 'ipv6' => string, 'port' => int, 'peer_tag' => bytes, ];
```  

