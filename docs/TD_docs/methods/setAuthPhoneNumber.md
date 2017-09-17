---
title: setAuthPhoneNumber
description: Sets user's phone number and sends authentication code to the user. Works only when getAuthState returns authStateWaitPhoneNumber. If phone number is not recognized or another error has happened, returns an error. Otherwise returns authStateWaitCode
---
## Method: setAuthPhoneNumber  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sets user's phone number and sends authentication code to the user. Works only when getAuthState returns authStateWaitPhoneNumber. If phone number is not recognized or another error has happened, returns an error. Otherwise returns authStateWaitCode

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|phone\_number|[string](../types/string.md) | Yes|User's phone number in any reasonable format|
|allow\_flash\_call|[Bool](../types/Bool.md) | Yes|Pass True, if code can be sent via flash call to the specified phone number|
|is\_current\_phone\_number|[Bool](../types/Bool.md) | Yes|Pass true, if the phone number is used on the current device. Ignored if allow_flash_call is False|


### Return type: [AuthState](../types/AuthState.md)

