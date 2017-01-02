---
title: authorization
description: authorization attributes, type and example
---
## Constructor: authorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[long](../types/long.md) | Required|
|device\_model|[string](../types/string.md) | Required|
|platform|[string](../types/string.md) | Required|
|system\_version|[string](../types/string.md) | Required|
|api\_id|[int](../types/int.md) | Required|
|app\_name|[string](../types/string.md) | Required|
|app\_version|[string](../types/string.md) | Required|
|date\_created|[int](../types/int.md) | Required|
|date\_active|[int](../types/int.md) | Required|
|ip|[string](../types/string.md) | Required|
|country|[string](../types/string.md) | Required|
|region|[string](../types/string.md) | Required|



### Type: [Authorization](../types/Authorization.md)


### Example:

```
$authorization = ['_' => 'authorization', 'hash' => long, 'device_model' => string, 'platform' => string, 'system_version' => string, 'api_id' => int, 'app_name' => string, 'app_version' => string, 'date_created' => int, 'date_active' => int, 'ip' => string, 'country' => string, 'region' => string, ];
```