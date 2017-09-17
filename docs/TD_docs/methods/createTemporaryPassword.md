---
title: createTemporaryPassword
description: Creates new temporary password for payments processing
---
## Method: createTemporaryPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Creates new temporary password for payments processing

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|password|[string](../types/string.md) | Yes|Persistent user password|
|valid\_for|[int](../types/int.md) | Yes|Time before temporary password will expire, seconds. Should be between 60 and 86400|


### Return type: [TemporaryPasswordState](../types/TemporaryPasswordState.md)

