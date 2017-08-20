---
title: updateStickerSetsOrder
description: updateStickerSetsOrder attributes, type and example
---
## Constructor: updateStickerSetsOrder  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[Bool](../types/Bool.md) | Optional|
|order|Array of [long](../types/long.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateStickerSetsOrder = ['_' => 'updateStickerSetsOrder', 'masks' => Bool, 'order' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateStickerSetsOrder", "masks": Bool, "order": [long]}
```


Or, if you're into Lua:  


```
updateStickerSetsOrder={_='updateStickerSetsOrder', masks=Bool, order={long}}

```


