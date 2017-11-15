---
title: account.passwordSettings
description: account_passwordSettings attributes, type and example
---
## Constructor: account.passwordSettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|email|[string](../types/string.md) | Yes|



### Type: [account\_PasswordSettings](../types/account_PasswordSettings.md)


### Example:

```
$account_passwordSettings = ['_' => 'account.passwordSettings', 'email' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.passwordSettings", "email": "string"}
```


Or, if you're into Lua:  


```
account_passwordSettings={_='account.passwordSettings', email='string'}

```


