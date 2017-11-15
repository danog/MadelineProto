---
title: bad_server_salt
description: bad_server_salt attributes, type and example
---
## Constructor: bad\_server\_salt  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bad\_msg\_id|[long](../types/long.md) | Yes|
|bad\_msg\_seqno|[int](../types/int.md) | Yes|
|error\_code|[int](../types/int.md) | Yes|
|new\_server\_salt|[long](../types/long.md) | Yes|



### Type: [BadMsgNotification](../types/BadMsgNotification.md)


### Example:

```
$bad_server_salt = ['_' => 'bad_server_salt', 'bad_msg_id' => long, 'bad_msg_seqno' => int, 'error_code' => int, 'new_server_salt' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "bad_server_salt", "bad_msg_id": long, "bad_msg_seqno": int, "error_code": int, "new_server_salt": long}
```


Or, if you're into Lua:  


```
bad_server_salt={_='bad_server_salt', bad_msg_id=long, bad_msg_seqno=int, error_code=int, new_server_salt=long}

```


