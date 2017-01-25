---
title: userEmpty
description: userEmpty attributes, type and example
---
## Constructor: userEmpty  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|



### Type: [User](../types/User.md)


### Example:

```
$userEmpty = ['_' => 'userEmpty', 'id' => int, ];
```  

The following syntaxes can also be used:

```
$userEmpty = '@username'; // Username

$userEmpty = 44700; // bot API id (users)
$userEmpty = -492772765; // bot API id (chats)
$userEmpty = -10038575794; // bot API id (channels)

$userEmpty = 'user#44700'; // tg-cli style id (users)
$userEmpty = 'chat#492772765'; // tg-cli style id (chats)
$userEmpty = 'channel#38575794'; // tg-cli style id (channels)
```