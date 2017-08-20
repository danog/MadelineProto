---
title: auth.sentCode
description: auth_sentCode attributes, type and example
---
## Constructor: auth.sentCode  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_registered|[Bool](../types/Bool.md) | Yes|
|phone\_code\_hash|[string](../types/string.md) | Yes|
|send\_call\_timeout|[int](../types/int.md) | Yes|
|is\_password|[Bool](../types/Bool.md) | Yes|



### Type: [auth\_SentCode](../types/auth_SentCode.md)


### Example:

```
$auth_sentCode = ['_' => 'auth.sentCode', 'phone_registered' => Bool, 'phone_code_hash' => 'string', 'send_call_timeout' => int, 'is_password' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.sentCode", "phone_registered": Bool, "phone_code_hash": "string", "send_call_timeout": int, "is_password": Bool}
```


Or, if you're into Lua:  


```
auth_sentCode={_='auth.sentCode', phone_registered=Bool, phone_code_hash='string', send_call_timeout=int, is_password=Bool}

```


