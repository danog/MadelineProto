---
title: updatePhoneCall
description: updatePhoneCall attributes, type and example
---
## Constructor: updatePhoneCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_call|[PhoneCall](../types/PhoneCall.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updatePhoneCall = ['_' => 'updatePhoneCall', 'phone_call' => PhoneCall];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updatePhoneCall", "phone_call": PhoneCall}
```


Or, if you're into Lua:  


```
updatePhoneCall={_='updatePhoneCall', phone_call=PhoneCall}

```


