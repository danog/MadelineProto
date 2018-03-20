---
title: account.webAuthorizations
description: account_webAuthorizations attributes, type and example
---
## Constructor: account.webAuthorizations  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|authorizations|Array of [WebAuthorization](../types/WebAuthorization.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [account\_WebAuthorizations](../types/account_WebAuthorizations.md)


### Example:

```
$account_webAuthorizations = ['_' => 'account.webAuthorizations', 'authorizations' => [WebAuthorization, WebAuthorization], 'users' => [User, User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.webAuthorizations", "authorizations": [WebAuthorization], "users": [User]}
```


Or, if you're into Lua:  


```
account_webAuthorizations={_='account.webAuthorizations', authorizations={WebAuthorization}, users={User}}

```


