---
title: updateFileGenerationProgress
description: Informs that a file is being generated
---
## Constructor: updateFileGenerationProgress  
[Back to constructors index](index.md)



Informs that a file is being generated

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file\_id|[int](../types/int.md) | Yes|File identifier|
|size|[int](../types/int.md) | Yes|Expected size of the generated file|
|ready|[int](../types/int.md) | Yes|Number of bytes already generated. Negative number means that generation has failed and was terminated|



### Type: [Update](../types/Update.md)


### Example:

```
$updateFileGenerationProgress = ['_' => 'updateFileGenerationProgress', 'file_id' => int, 'size' => int, 'ready' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateFileGenerationProgress", "file_id": int, "size": int, "ready": int}
```


Or, if you're into Lua:  


```
updateFileGenerationProgress={_='updateFileGenerationProgress', file_id=int, size=int, ready=int}

```


