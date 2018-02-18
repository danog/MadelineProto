---
title: updateLangPack
description: updateLangPack attributes, type and example
---
## Constructor: updateLangPack  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|difference|[LangPackDifference](../types/LangPackDifference.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateLangPack = ['_' => 'updateLangPack', 'difference' => LangPackDifference];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateLangPack", "difference": LangPackDifference}
```


Or, if you're into Lua:  


```
updateLangPack={_='updateLangPack', difference=LangPackDifference}

```


