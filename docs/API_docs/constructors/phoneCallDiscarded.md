---
title: phoneCallDiscarded
description: phoneCallDiscarded attributes, type and example
---
## Constructor: phoneCallDiscarded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|need\_rating|[Bool](../types/Bool.md) | Optional|
|need\_debug|[Bool](../types/Bool.md) | Optional|
|id|[long](../types/long.md) | Yes|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Optional|
|duration|[int](../types/int.md) | Optional|



### Type: [PhoneCall](../types/PhoneCall.md)


### Example:

```
$phoneCallDiscarded = ['_' => 'phoneCallDiscarded', 'need_rating' => Bool, 'need_debug' => Bool, 'id' => long, 'reason' => PhoneCallDiscardReason, 'duration' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "phoneCallDiscarded", "need_rating": Bool, "need_debug": Bool, "id": long, "reason": PhoneCallDiscardReason, "duration": int}
```


Or, if you're into Lua:  


```
phoneCallDiscarded={_='phoneCallDiscarded', need_rating=Bool, need_debug=Bool, id=long, reason=PhoneCallDiscardReason, duration=int}

```


