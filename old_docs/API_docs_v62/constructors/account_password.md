---
title: account.password
description: account_password attributes, type and example
---
## Constructor: account.password  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|current\_salt|[bytes](../types/bytes.md) | Yes|
|new\_salt|[bytes](../types/bytes.md) | Yes|
|hint|[string](../types/string.md) | Yes|
|has\_recovery|[Bool](../types/Bool.md) | Yes|
|email\_unconfirmed\_pattern|[string](../types/string.md) | Yes|



### Type: [account\_Password](../types/account_Password.md)


### Example:

```
$account_password = ['_' => 'account.password', 'current_salt' => 'bytes', 'new_salt' => 'bytes', 'hint' => 'string', 'has_recovery' => Bool, 'email_unconfirmed_pattern' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.password", "current_salt": "bytes", "new_salt": "bytes", "hint": "string", "has_recovery": Bool, "email_unconfirmed_pattern": "string"}
```


Or, if you're into Lua:  


```
account_password={_='account.password', current_salt='bytes', new_salt='bytes', hint='string', has_recovery=Bool, email_unconfirmed_pattern='string'}

```


