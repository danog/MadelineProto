---
title: updateBotWebhookJSON
description: updateBotWebhookJSON attributes, type and example
---
## Constructor: updateBotWebhookJSON  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|data|[DataJSON](../types/DataJSON.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotWebhookJSON = ['_' => 'updateBotWebhookJSON', 'data' => DataJSON];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateBotWebhookJSON", "data": DataJSON}
```


Or, if you're into Lua:  


```
updateBotWebhookJSON={_='updateBotWebhookJSON', data=DataJSON}

```


