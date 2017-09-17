---
title: setRecoveryEmail
description: Changes user recovery email. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and email will not be changed until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed. -If new_recovery_email coincides with the current set up email succeeds immediately and aborts all other requests waiting for email confirmation
---
## Method: setRecoveryEmail  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes user recovery email. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and email will not be changed until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed. -If new_recovery_email coincides with the current set up email succeeds immediately and aborts all other requests waiting for email confirmation

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|password|[string](../types/string.md) | Yes|Current user password|
|new\_recovery\_email|[string](../types/string.md) | Yes|New recovery email|


### Return type: [PasswordState](../types/PasswordState.md)

