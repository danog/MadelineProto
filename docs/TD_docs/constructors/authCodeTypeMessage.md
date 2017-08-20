---
title: authCodeTypeMessage
description: Code is delivered through private Telegram message, which can be viewed in the other client
---
## Constructor: authCodeTypeMessage  
[Back to constructors index](index.md)



Code is delivered through private Telegram message, which can be viewed in the other client

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|length|[int](../types/int.md) | Yes|Length of the code|



### Type: [AuthCodeType](../types/AuthCodeType.md)


### Example:

```
$authCodeTypeMessage = ['_' => 'authCodeTypeMessage', 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "authCodeTypeMessage", "length": int}
```


Or, if you're into Lua:  


```
authCodeTypeMessage={_='authCodeTypeMessage', length=int}

```


