---
title: inputBotInlineMessageID
description: inputBotInlineMessageID attributes, type and example
---
## Constructor: inputBotInlineMessageID  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dc\_id|[int](../types/int.md) | Yes|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputBotInlineMessageID](../types/InputBotInlineMessageID.md)


### Example:

```
$inputBotInlineMessageID = ['_' => 'inputBotInlineMessageID', 'dc_id' => int, 'id' => long, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineMessageID", "dc_id": int, "id": long, "access_hash": long}
```


Or, if you're into Lua:  


```
inputBotInlineMessageID={_='inputBotInlineMessageID', dc_id=int, id=long, access_hash=long}

```


