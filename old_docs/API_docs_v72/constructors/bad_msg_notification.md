---
title: bad_msg_notification
description: bad_msg_notification attributes, type and example
---
## Constructor: bad\_msg\_notification  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bad\_msg\_id|[long](../types/long.md) | Yes|
|bad\_msg\_seqno|[int](../types/int.md) | Yes|
|error\_code|[int](../types/int.md) | Yes|



### Type: [BadMsgNotification](../types/BadMsgNotification.md)


### Example:

```
$bad_msg_notification = ['_' => 'bad_msg_notification', 'bad_msg_id' => long, 'bad_msg_seqno' => int, 'error_code' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "bad_msg_notification", "bad_msg_id": long, "bad_msg_seqno": int, "error_code": int}
```


Or, if you're into Lua:  


```
bad_msg_notification={_='bad_msg_notification', bad_msg_id=long, bad_msg_seqno=int, error_code=int}

```


