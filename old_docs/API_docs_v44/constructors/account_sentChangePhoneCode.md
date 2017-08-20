---
title: account.sentChangePhoneCode
description: account_sentChangePhoneCode attributes, type and example
---
## Constructor: account.sentChangePhoneCode  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_code\_hash|[string](../types/string.md) | Yes|
|send\_call\_timeout|[int](../types/int.md) | Yes|



### Type: [account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)


### Example:

```
$account_sentChangePhoneCode = ['_' => 'account.sentChangePhoneCode', 'phone_code_hash' => 'string', 'send_call_timeout' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "account.sentChangePhoneCode", "phone_code_hash": "string", "send_call_timeout": int}
```


Or, if you're into Lua:  


```
account_sentChangePhoneCode={_='account.sentChangePhoneCode', phone_code_hash='string', send_call_timeout=int}

```


