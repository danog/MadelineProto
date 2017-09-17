---
title: setPassword
description: Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed
---
## Method: setPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|old\_password|[string](../types/string.md) | Yes|Old user password|
|new\_password|[string](../types/string.md) | Yes|New user password, may be empty to remove the password|
|new\_hint|[string](../types/string.md) | Yes|New password hint, can be empty|
|set\_recovery\_email|[Bool](../types/Bool.md) | Yes|Pass True, if recovery email should be changed|
|new\_recovery\_email|[string](../types/string.md) | Yes|New recovery email, may be empty|


### Return type: [PasswordState](../types/PasswordState.md)

