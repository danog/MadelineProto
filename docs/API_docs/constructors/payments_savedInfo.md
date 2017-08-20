---
title: payments.savedInfo
description: payments_savedInfo attributes, type and example
---
## Constructor: payments.savedInfo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|has\_saved\_credentials|[Bool](../types/Bool.md) | Optional|
|saved\_info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Optional|



### Type: [payments\_SavedInfo](../types/payments_SavedInfo.md)


### Example:

```
$payments_savedInfo = ['_' => 'payments.savedInfo', 'has_saved_credentials' => Bool, 'saved_info' => PaymentRequestedInfo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "payments.savedInfo", "has_saved_credentials": Bool, "saved_info": PaymentRequestedInfo}
```


Or, if you're into Lua:  


```
payments_savedInfo={_='payments.savedInfo', has_saved_credentials=Bool, saved_info=PaymentRequestedInfo}

```


