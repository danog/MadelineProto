---
title: auth.passwordRecovery
description: auth_passwordRecovery attributes, type and example
---
## Constructor: auth.passwordRecovery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|email\_pattern|[string](../types/string.md) | Yes|



### Type: [auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)


### Example:

```
$auth_passwordRecovery = ['_' => 'auth.passwordRecovery', 'email_pattern' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.passwordRecovery", "email_pattern": "string"}
```


Or, if you're into Lua:  


```
auth_passwordRecovery={_='auth.passwordRecovery', email_pattern='string'}

```


