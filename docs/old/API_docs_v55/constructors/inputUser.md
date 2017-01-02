---
title: inputUser
description: inputUser attributes, type and example
---
## Constructor: inputUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|



### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUser = ['_' => 'inputUser', 'user_id' => int, 'access_hash' => long, ];
```  

The following syntaxes can also be used:

```
$inputUser = '@username'; // Username

$inputUser = 44700; // bot API id (users)
$inputUser = -492772765; // bot API id (chats)
$inputUser = -10038575794; // bot API id (channels)

$inputUser = 'user#44700'; // tg-cli style id (users)
$inputUser = 'chat#492772765'; // tg-cli style id (chats)
$inputUser = 'channel#38575794'; // tg-cli style id (channels)
```