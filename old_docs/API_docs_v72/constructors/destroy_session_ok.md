---
title: destroy_session_ok
description: destroy_session_ok attributes, type and example
---
## Constructor: destroy\_session\_ok  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|session\_id|[long](../types/long.md) | Yes|



### Type: [DestroySessionRes](../types/DestroySessionRes.md)


### Example:

```
$destroy_session_ok = ['_' => 'destroy_session_ok', 'session_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "destroy_session_ok", "session_id": long}
```


Or, if you're into Lua:  


```
destroy_session_ok={_='destroy_session_ok', session_id=long}

```


