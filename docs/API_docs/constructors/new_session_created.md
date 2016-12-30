---
title: new_session_created
description: new_session_created attributes, type and example
---
## Constructor: new\_session\_created  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|first\_msg\_id|[long](../types/long.md) | Required|
|unique\_id|[long](../types/long.md) | Required|
|server\_salt|[long](../types/long.md) | Required|



### Type: [NewSession](../types/NewSession.md)


### Example:

```
$new_session_created = ['_' => 'new_session_created', 'first_msg_id' => long, 'unique_id' => long, 'server_salt' => long, ];
```