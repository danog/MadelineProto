---
title: inputGameShortName
description: inputGameShortName attributes, type and example
---
## Constructor: inputGameShortName  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot\_id|[InputUser](../types/InputUser.md) | Yes|
|short\_name|[string](../types/string.md) | Yes|



### Type: [InputGame](../types/InputGame.md)


### Example:

```
$inputGameShortName = ['_' => 'inputGameShortName', 'bot_id' => InputUser, 'short_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputGameShortName", "bot_id": InputUser, "short_name": "string"}
```


Or, if you're into Lua:  


```
inputGameShortName={_='inputGameShortName', bot_id=InputUser, short_name='string'}

```


