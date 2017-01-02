---
title: inputUserContact
description: inputUserContact attributes, type and example
---
## Constructor: inputUserContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|



### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUserContact = ['_' => 'inputUserContact', 'user_id' => int, ];
```  

The following syntaxes can also be used:

```
$inputUserContact = '@username'; // Username

$inputUserContact = 44700; // bot API id (users)
$inputUserContact = -492772765; // bot API id (chats)
$inputUserContact = -10038575794; // bot API id (channels)

$inputUserContact = 'user#44700'; // tg-cli style id (users)
$inputUserContact = 'chat#492772765'; // tg-cli style id (chats)
$inputUserContact = 'channel#38575794'; // tg-cli style id (channels)
```