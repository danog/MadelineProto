---
title: auth.checkedPhone
description: auth_checkedPhone attributes, type and example
---
## Constructor: auth.checkedPhone  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_registered|[Bool](../types/Bool.md) | Yes|
|phone\_invited|[Bool](../types/Bool.md) | Yes|



### Type: [auth\_CheckedPhone](../types/auth_CheckedPhone.md)


### Example:

```
$auth_checkedPhone = ['_' => 'auth.checkedPhone', 'phone_registered' => Bool, 'phone_invited' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.checkedPhone", "phone_registered": Bool, "phone_invited": Bool}
```


Or, if you're into Lua:  


```
auth_checkedPhone={_='auth.checkedPhone', phone_registered=Bool, phone_invited=Bool}

```


