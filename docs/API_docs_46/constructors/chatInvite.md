---
title: chatInvite
description: chatInvite attributes, type and example
---
## Constructor: chatInvite  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|public|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|title|[string](../types/string.md) | Required|



### Type: [ChatInvite](../types/ChatInvite.md)


### Example:

```
$chatInvite = ['_' => 'chatInvite', 'channel' => true, 'broadcast' => true, 'public' => true, 'megagroup' => true, 'title' => string, ];
```