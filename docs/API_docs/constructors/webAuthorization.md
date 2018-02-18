---
title: webAuthorization
description: webAuthorization attributes, type and example
---
## Constructor: webAuthorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[long](../types/long.md) | Yes|
|bot\_id|[int](../types/int.md) | Yes|
|domain|[string](../types/string.md) | Yes|
|browser|[string](../types/string.md) | Yes|
|platform|[string](../types/string.md) | Yes|
|date\_created|[int](../types/int.md) | Yes|
|date\_active|[int](../types/int.md) | Yes|
|ip|[string](../types/string.md) | Yes|
|region|[string](../types/string.md) | Yes|



### Type: [WebAuthorization](../types/WebAuthorization.md)


### Example:

```
$webAuthorization = ['_' => 'webAuthorization', 'hash' => long, 'bot_id' => int, 'domain' => 'string', 'browser' => 'string', 'platform' => 'string', 'date_created' => int, 'date_active' => int, 'ip' => 'string', 'region' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "webAuthorization", "hash": long, "bot_id": int, "domain": "string", "browser": "string", "platform": "string", "date_created": int, "date_active": int, "ip": "string", "region": "string"}
```


Or, if you're into Lua:  


```
webAuthorization={_='webAuthorization', hash=long, bot_id=int, domain='string', browser='string', platform='string', date_created=int, date_active=int, ip='string', region='string'}

```


