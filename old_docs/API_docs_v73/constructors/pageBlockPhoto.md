---
title: pageBlockPhoto
description: pageBlockPhoto attributes, type and example
---
## Constructor: pageBlockPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photo\_id|[long](../types/long.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockPhoto = ['_' => 'pageBlockPhoto', 'photo_id' => long, 'caption' => RichText];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockPhoto", "photo_id": long, "caption": RichText}
```


Or, if you're into Lua:  


```
pageBlockPhoto={_='pageBlockPhoto', photo_id=long, caption=RichText}

```


