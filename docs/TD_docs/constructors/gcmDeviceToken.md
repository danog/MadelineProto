---
title: gcmDeviceToken
description: Token for GCM
---
## Constructor: gcmDeviceToken  
[Back to constructors index](index.md)



Token for GCM

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|token|[string](../types/string.md) | Yes|The token|



### Type: [DeviceToken](../types/DeviceToken.md)


### Example:

```
$gcmDeviceToken = ['_' => 'gcmDeviceToken', 'token' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "gcmDeviceToken", "token": "string"}
```


Or, if you're into Lua:  


```
gcmDeviceToken={_='gcmDeviceToken', token='string'}

```


