---
title: phone.phoneCall
description: phone_phoneCall attributes, type and example
---
## Constructor: phone.phoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_call|[PhoneCall](../types/PhoneCall.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [phone\_PhoneCall](../types/phone_PhoneCall.md)


### Example:

```
$phone_phoneCall = ['_' => 'phone.phoneCall', 'phone_call' => PhoneCall, 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "phone.phoneCall", "phone_call": PhoneCall, "users": [User]}
```


Or, if you're into Lua:  


```
phone_phoneCall={_='phone.phoneCall', phone_call=PhoneCall, users={User}}

```


