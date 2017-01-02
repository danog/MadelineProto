---
title: inputUserForeign
description: inputUserForeign attributes, type and example
---
## Constructor: inputUserForeign  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|



### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUserForeign = ['_' => 'inputUserForeign', 'user_id' => int, 'access_hash' => long, ];
```  

The following syntaxes can also be used:

```
$inputUserForeign = '@username'; // Username

$inputUserForeign = 44700; // bot API id (users)
$inputUserForeign = -492772765; // bot API id (chats)
$inputUserForeign = -10038575794; // bot API id (channels)

$inputUserForeign = 'user#44700'; // tg-cli style id (users)
$inputUserForeign = 'chat#492772765'; // tg-cli style id (chats)
$inputUserForeign = 'channel#38575794'; // tg-cli style id (channels)
```