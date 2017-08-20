---
title: account.privacyRules
description: account_privacyRules attributes, type and example
---
## Constructor: account.privacyRules  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|rules|Array of [PrivacyRule](../types/PrivacyRule.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [account\_PrivacyRules](../types/account_PrivacyRules.md)


### Example:

```
$account_privacyRules = ['_' => 'account.privacyRules', 'rules' => [PrivacyRule], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.privacyRules", "rules": [PrivacyRule], "users": [User]}
```


Or, if you're into Lua:  


```
account_privacyRules={_='account.privacyRules', rules={PrivacyRule}, users={User}}

```


