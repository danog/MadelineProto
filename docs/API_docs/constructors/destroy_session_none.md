---
title: destroy_session_none
description: destroy_session_none attributes, type and example
---
## Constructor: destroy\_session\_none  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|session\_id|[long](../types/long.md) | Yes|



### Type: [DestroySessionRes](../types/DestroySessionRes.md)


### Example:

```
$destroy_session_none = ['_' => 'destroy_session_none', 'session_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "destroy_session_none", "session_id": long}
```


Or, if you're into Lua:  


```
destroy_session_none={_='destroy_session_none', session_id=long}

```


