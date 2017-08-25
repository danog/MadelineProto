---
title: error
description: Object of this type may be returned on every function call in case of the error
---
## Constructor: error  
[Back to constructors index](index.md)



Object of this type may be returned on every function call in case of the error

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[int](../types/int.md) | Yes|Error code, maybe changed in the future. If code == 406, error message should not be processed in any way and shouldn't be showed to the user|
|message|[string](../types/string.md) | Yes|Error message, may be changed in the future|



### Type: [Error](../types/Error.md)


