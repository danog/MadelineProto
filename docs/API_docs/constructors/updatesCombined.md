---
title: updatesCombined
description: updatesCombined attributes, type and example
---
## Constructor: updatesCombined  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|updates|Array of [Update](../types/Update.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|date|[int](../types/int.md) | Required|
|seq\_start|[int](../types/int.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updatesCombined = ['_' => updatesCombined', 'updates' => [Vector t], 'users' => [Vector t], 'chats' => [Vector t], 'date' => int, 'seq_start' => int, 'seq' => int, ];
```