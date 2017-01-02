---
title: inputUserSelf
description: inputUserSelf attributes, type and example
---
## Constructor: inputUserSelf  
[Back to constructors index](index.md)






### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUserSelf = ['_' => 'inputUserSelf', ];
```  

The following syntaxes can also be used:

```
$inputUserSelf = '@username'; // Username

$inputUserSelf = 44700; // bot API id (users)
$inputUserSelf = -492772765; // bot API id (chats)
$inputUserSelf = -10038575794; // bot API id (channels)

$inputUserSelf = 'user#44700'; // tg-cli style id (users)
$inputUserSelf = 'chat#492772765'; // tg-cli style id (chats)
$inputUserSelf = 'channel#38575794'; // tg-cli style id (channels)
```