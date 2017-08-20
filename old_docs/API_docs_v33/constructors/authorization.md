---
title: authorization
description: authorization attributes, type and example
---
## Constructor: authorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[long](../types/long.md) | Yes|
|device\_model|[string](../types/string.md) | Yes|
|platform|[string](../types/string.md) | Yes|
|system\_version|[string](../types/string.md) | Yes|
|api\_id|[int](../types/int.md) | Yes|
|app\_name|[string](../types/string.md) | Yes|
|app\_version|[string](../types/string.md) | Yes|
|date\_created|[int](../types/int.md) | Yes|
|date\_active|[int](../types/int.md) | Yes|
|ip|[string](../types/string.md) | Yes|
|country|[string](../types/string.md) | Yes|
|region|[string](../types/string.md) | Yes|



### Type: [Authorization](../types/Authorization.md)


### Example:

```
$authorization = ['_' => 'authorization', 'hash' => long, 'device_model' => 'string', 'platform' => 'string', 'system_version' => 'string', 'api_id' => int, 'app_name' => 'string', 'app_version' => 'string', 'date_created' => int, 'date_active' => int, 'ip' => 'string', 'country' => 'string', 'region' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "authorization", "hash": long, "device_model": "string", "platform": "string", "system_version": "string", "api_id": int, "app_name": "string", "app_version": "string", "date_created": int, "date_active": int, "ip": "string", "country": "string", "region": "string"}
```


Or, if you're into Lua:  


```
authorization={_='authorization', hash=long, device_model='string', platform='string', system_version='string', api_id=int, app_name='string', app_version='string', date_created=int, date_active=int, ip='string', country='string', region='string'}

```


