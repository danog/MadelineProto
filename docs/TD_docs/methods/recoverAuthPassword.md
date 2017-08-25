---
title: recoverAuthPassword
description: Recovers password with recovery code sent to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success
---
## Method: recoverAuthPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Recovers password with recovery code sent to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|recovery\_code|[string](../types/string.md) | Yes|Recovery code to check|


### Return type: [AuthState](../types/AuthState.md)

