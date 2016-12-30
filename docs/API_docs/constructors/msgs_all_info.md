---
title: msgs_all_info
description: msgs_all_info attributes, type and example
---
## Constructor: msgs\_all\_info  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|msg\_ids|Array of [long](../types/long.md) | Required|
|info|[bytes](../types/bytes.md) | Required|



### Type: [MsgsAllInfo](../types/MsgsAllInfo.md)


### Example:

```
$msgs_all_info = ['_' => 'msgs_all_info', 'msg_ids' => [Vector t], 'info' => bytes, ];
```