---
title: updateFileGenerationFinish
description: File generation is finished
---
## Constructor: updateFileGenerationFinish  
[Back to constructors index](index.md)



File generation is finished

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file|[file](../types/file.md) | Yes|Generated file|



### Type: [Update](../types/Update.md)


### Example:

```
$updateFileGenerationFinish = ['_' => 'updateFileGenerationFinish', 'file' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateFileGenerationFinish", "file": file}
```


Or, if you're into Lua:  


```
updateFileGenerationFinish={_='updateFileGenerationFinish', file=file}

```


