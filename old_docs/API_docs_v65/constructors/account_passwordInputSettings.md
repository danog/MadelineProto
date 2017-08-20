---
title: account.passwordInputSettings
description: account_passwordInputSettings attributes, type and example
---
## Constructor: account.passwordInputSettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|new\_salt|[bytes](../types/bytes.md) | Optional|
|new\_password\_hash|[bytes](../types/bytes.md) | Optional|
|hint|[string](../types/string.md) | Optional|
|email|[string](../types/string.md) | Optional|



### Type: [account\_PasswordInputSettings](../types/account_PasswordInputSettings.md)


### Example:

```
$account_passwordInputSettings = ['_' => 'account.passwordInputSettings', 'new_salt' => 'bytes', 'new_password_hash' => 'bytes', 'hint' => 'string', 'email' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.passwordInputSettings", "new_salt": "bytes", "new_password_hash": "bytes", "hint": "string", "email": "string"}
```


Or, if you're into Lua:  


```
account_passwordInputSettings={_='account.passwordInputSettings', new_salt='bytes', new_password_hash='bytes', hint='string', email='string'}

```


