---
title: channelForbidden
description: channelForbidden attributes, type and example
---
## Constructor: channelForbidden  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|broadcast|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channelForbidden = ['_' => 'channelForbidden', 'broadcast' => true, 'megagroup' => true, 'id' => int, 'access_hash' => long, 'title' => string, ];
```  

The following syntaxes can also be used:

```
$channelForbidden = '@username'; // Username

$channelForbidden = 44700; // bot API id (users)
$channelForbidden = -492772765; // bot API id (chats)
$channelForbidden = -10038575794; // bot API id (channels)

$channelForbidden = 'user#44700'; // tg-cli style id (users)
$channelForbidden = 'chat#492772765'; // tg-cli style id (chats)
$channelForbidden = 'channel#38575794'; // tg-cli style id (channels)
```