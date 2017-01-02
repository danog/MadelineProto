---
title: chatEmpty
description: chatEmpty attributes, type and example
---
## Constructor: chatEmpty  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chatEmpty = ['_' => 'chatEmpty', 'id' => int, ];
```  

The following syntaxes can also be used:

```
$chatEmpty = '@username'; // Username

$chatEmpty = 44700; // bot API id (users)
$chatEmpty = -492772765; // bot API id (chats)
$chatEmpty = -10038575794; // bot API id (channels)

$chatEmpty = 'user#44700'; // tg-cli style id (users)
$chatEmpty = 'chat#492772765'; // tg-cli style id (chats)
$chatEmpty = 'channel#38575794'; // tg-cli style id (channels)
```