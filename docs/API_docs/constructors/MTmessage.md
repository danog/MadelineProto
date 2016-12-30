---
title: MTmessage
description: MTmessage attributes, type and example
---
## Constructor: MTmessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|msg\_id|[long](../types/long.md) | Required|
|seqno|[int](../types/int.md) | Required|
|bytes|[int](../types/int.md) | Required|
|body|[Object](../types/Object.md) | Required|



### Type: [Message](../types/Message.md)


### Example:

```
$MTmessage = ['_' => MTmessage, 'msg_id' => long, 'seqno' => int, 'bytes' => int, 'body' => Object, ];
```