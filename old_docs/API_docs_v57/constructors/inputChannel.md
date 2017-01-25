---
title: inputChannel
description: inputChannel attributes, type and example
---
## Constructor: inputChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|



### Type: [InputChannel](../types/InputChannel.md)


### Example:

```
$inputChannel = ['_' => 'inputChannel', 'channel_id' => int, 'access_hash' => long, ];
```  

The following syntaxes can also be used:

```
$inputChannel = '@username'; // Username

$inputChannel = 44700; // bot API id (users)
$inputChannel = -492772765; // bot API id (chats)
$inputChannel = -10038575794; // bot API id (channels)

$inputChannel = 'user#44700'; // tg-cli style id (users)
$inputChannel = 'chat#492772765'; // tg-cli style id (chats)
$inputChannel = 'channel#38575794'; // tg-cli style id (channels)
```