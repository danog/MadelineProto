---
title: inputPaymentCredentialsSaved
description: inputPaymentCredentialsSaved attributes, type and example
---
## Constructor: inputPaymentCredentialsSaved  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|tmp\_password|[bytes](../types/bytes.md) | Yes|



### Type: [InputPaymentCredentials](../types/InputPaymentCredentials.md)


### Example:

```
$inputPaymentCredentialsSaved = ['_' => 'inputPaymentCredentialsSaved', 'id' => 'string', 'tmp_password' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPaymentCredentialsSaved", "id": "string", "tmp_password": "bytes"}
```


Or, if you're into Lua:  


```
inputPaymentCredentialsSaved={_='inputPaymentCredentialsSaved', id='string', tmp_password='bytes'}

```


