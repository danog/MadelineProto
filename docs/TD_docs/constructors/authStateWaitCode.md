---
title: authStateWaitCode
description: TDLib needs user authentication code to finish authorization
---
## Constructor: authStateWaitCode  
[Back to constructors index](index.md)



TDLib needs user authentication code to finish authorization

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_registered|[Bool](../types/Bool.md) | Yes|True, if user is already registered|
|code\_type|[AuthCodeType](../types/AuthCodeType.md) | Yes|Describes the way, code was sent to the user|
|next\_code\_type|[AuthCodeType](../types/AuthCodeType.md) | Yes|Describes the way, next code will be sent to the user, nullable|
|timeout|[int](../types/int.md) | Yes|Timeout in seconds before code should be resent by calling resendAuthCode|



### Type: [AuthState](../types/AuthState.md)


