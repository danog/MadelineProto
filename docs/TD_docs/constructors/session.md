---
title: session
description: Contains information about one session in some application used by the user
---
## Constructor: session  
[Back to constructors index](index.md)



Contains information about one session in some application used by the user

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Session identifier|
|is\_current|[Bool](../types/Bool.md) | Yes|True, if it is current session|
|app\_id|[int](../types/int.md) | Yes|Application identifier, provided by the application|
|app\_name|[string](../types/string.md) | Yes|Name of the application, provided by the application|
|app\_version|[string](../types/string.md) | Yes|Version of the application, provided by the application|
|is\_official\_app|[Bool](../types/Bool.md) | Yes|True, if the application is an official application or uses the app_id of some official application|
|device\_model|[string](../types/string.md) | Yes|Model of a device application is runned on, provided by the application|
|platform|[string](../types/string.md) | Yes|Operating system application is runned on, provided by the application|
|system\_version|[string](../types/string.md) | Yes|Version of operating system application is runned on, provided by the application|
|date\_created|[int](../types/int.md) | Yes|Date the user has logged in, unix time|
|date\_active|[int](../types/int.md) | Yes|Date the session was used last time, unix time|
|ip|[string](../types/string.md) | Yes|An ip address from which session was created in a human-readable format|
|country|[string](../types/string.md) | Yes|Two-letter country code from which session was created based on the ip|
|region|[string](../types/string.md) | Yes|Region code from which session was created based on the ip|



### Type: [Session](../types/Session.md)


### Example:

```
$session = ['_' => 'session', 'id' => long, 'is_current' => Bool, 'app_id' => int, 'app_name' => 'string', 'app_version' => 'string', 'is_official_app' => Bool, 'device_model' => 'string', 'platform' => 'string', 'system_version' => 'string', 'date_created' => int, 'date_active' => int, 'ip' => 'string', 'country' => 'string', 'region' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "session", "id": long, "is_current": Bool, "app_id": int, "app_name": "string", "app_version": "string", "is_official_app": Bool, "device_model": "string", "platform": "string", "system_version": "string", "date_created": int, "date_active": int, "ip": "string", "country": "string", "region": "string"}
```


Or, if you're into Lua:  


```
session={_='session', id=long, is_current=Bool, app_id=int, app_name='string', app_version='string', is_official_app=Bool, device_model='string', platform='string', system_version='string', date_created=int, date_active=int, ip='string', country='string', region='string'}

```


