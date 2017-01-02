---
title: inputUserEmpty
description: inputUserEmpty attributes, type and example
---
## Constructor: inputUserEmpty  
[Back to constructors index](index.md)






### Type: [InputUser](../types/InputUser.md)


### Example:

```
$inputUserEmpty = ['_' => 'inputUserEmpty', ];
```  

The following syntaxes can also be used:

```
$inputUserEmpty = '@username'; // Username

$inputUserEmpty = 44700; // bot API id (users)
$inputUserEmpty = -492772765; // bot API id (chats)
$inputUserEmpty = -10038575794; // bot API id (channels)

$inputUserEmpty = 'user#44700'; // tg-cli style id (users)
$inputUserEmpty = 'chat#492772765'; // tg-cli style id (chats)
$inputUserEmpty = 'channel#38575794'; // tg-cli style id (channels)
```