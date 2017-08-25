---
title: checkAuthCode
description: Checks authentication code. Works only when getAuthState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success
---
## Method: checkAuthCode  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Checks authentication code. Works only when getAuthState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[string](../types/string.md) | Yes|Verification code from SMS, Telegram message, phone call or flash call|
|first\_name|[string](../types/string.md) | Yes|User first name, if user is yet not registered, 1-255 characters|
|last\_name|[string](../types/string.md) | Yes|Optional user last name, if user is yet not registered, 0-255 characters|


### Return type: [AuthState](../types/AuthState.md)

