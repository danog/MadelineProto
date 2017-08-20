---
title: deviceTokenSet
description: Contains list of device tokens
---
## Constructor: deviceTokenSet  
[Back to constructors index](index.md)



Contains list of device tokens

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|tokens|Array of [DeviceToken](../constructors/DeviceToken.md) | Yes|List of tokens|



### Type: [DeviceTokenSet](../types/DeviceTokenSet.md)


### Example:

```
$deviceTokenSet = ['_' => 'deviceTokenSet', 'tokens' => [DeviceToken]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "deviceTokenSet", "tokens": [DeviceToken]}
```


Or, if you're into Lua:  


```
deviceTokenSet={_='deviceTokenSet', tokens={DeviceToken}}

```


