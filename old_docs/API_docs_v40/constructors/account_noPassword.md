---
title: account.noPassword
description: account_noPassword attributes, type and example
---
## Constructor: account.noPassword  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|new\_salt|[bytes](../types/bytes.md) | Yes|
|email\_unconfirmed\_pattern|[string](../types/string.md) | Yes|



### Type: [account\_Password](../types/account_Password.md)


### Example:

```
$account_noPassword = ['_' => 'account.noPassword', 'new_salt' => 'bytes', 'email_unconfirmed_pattern' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.noPassword", "new_salt": "bytes", "email_unconfirmed_pattern": "string"}
```


Or, if you're into Lua:  


```
account_noPassword={_='account.noPassword', new_salt='bytes', email_unconfirmed_pattern='string'}

```


