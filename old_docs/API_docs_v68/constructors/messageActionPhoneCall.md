---
title: messageActionPhoneCall
description: messageActionPhoneCall attributes, type and example
---
## Constructor: messageActionPhoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|call\_id|[long](../types/long.md) | Yes|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Optional|
|duration|[int](../types/int.md) | Optional|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionPhoneCall = ['_' => 'messageActionPhoneCall', 'call_id' => long, 'reason' => PhoneCallDiscardReason, 'duration' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionPhoneCall", "call_id": long, "reason": PhoneCallDiscardReason, "duration": int}
```


Or, if you're into Lua:  


```
messageActionPhoneCall={_='messageActionPhoneCall', call_id=long, reason=PhoneCallDiscardReason, duration=int}

```


