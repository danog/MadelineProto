---
title: account.authorizations
description: account_authorizations attributes, type and example
---
## Constructor: account.authorizations  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|authorizations|Array of [Authorization](../types/Authorization.md) | Yes|



### Type: [account\_Authorizations](../types/account_Authorizations.md)


### Example:

```
$account_authorizations = ['_' => 'account.authorizations', 'authorizations' => [Authorization]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.authorizations", "authorizations": [Authorization]}
```


Or, if you're into Lua:  


```
account_authorizations={_='account.authorizations', authorizations={Authorization}}

```


