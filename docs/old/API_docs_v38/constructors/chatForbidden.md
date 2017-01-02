---
title: chatForbidden
description: chatForbidden attributes, type and example
---
## Constructor: chatForbidden  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|title|[string](../types/string.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chatForbidden = ['_' => 'chatForbidden', 'id' => int, 'title' => string, ];
```  

The following syntaxes can also be used:

```
$chatForbidden = '@username'; // Username

$chatForbidden = 44700; // bot API id (users)
$chatForbidden = -492772765; // bot API id (chats)
$chatForbidden = -10038575794; // bot API id (channels)

$chatForbidden = 'user#44700'; // tg-cli style id (users)
$chatForbidden = 'chat#492772765'; // tg-cli style id (chats)
$chatForbidden = 'channel#38575794'; // tg-cli style id (channels)
```