---
title: userDeleted
description: userDeleted attributes, type and example
---
## Constructor: userDeleted  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Required|



### Type: [User](../types/User.md)


### Example:

```
$userDeleted = ['_' => 'userDeleted', 'id' => int, 'first_name' => string, 'last_name' => string, 'username' => string, ];
```  

The following syntaxes can also be used:

```
$userDeleted = '@username'; // Username

$userDeleted = 44700; // bot API id (users)
$userDeleted = -492772765; // bot API id (chats)
$userDeleted = -10038575794; // bot API id (channels)

$userDeleted = 'user#44700'; // tg-cli style id (users)
$userDeleted = 'chat#492772765'; // tg-cli style id (chats)
$userDeleted = 'channel#38575794'; // tg-cli style id (channels)
```