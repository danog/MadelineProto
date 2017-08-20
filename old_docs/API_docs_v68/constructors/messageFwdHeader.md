---
title: messageFwdHeader
description: messageFwdHeader attributes, type and example
---
## Constructor: messageFwdHeader  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|from\_id|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Yes|
|channel\_id|[int](../types/int.md) | Optional|
|channel\_post|[int](../types/int.md) | Optional|



### Type: [MessageFwdHeader](../types/MessageFwdHeader.md)


### Example:

```
$messageFwdHeader = ['_' => 'messageFwdHeader', 'from_id' => int, 'date' => int, 'channel_id' => int, 'channel_post' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageFwdHeader", "from_id": int, "date": int, "channel_id": int, "channel_post": int}
```


Or, if you're into Lua:  


```
messageFwdHeader={_='messageFwdHeader', from_id=int, date=int, channel_id=int, channel_post=int}

```


