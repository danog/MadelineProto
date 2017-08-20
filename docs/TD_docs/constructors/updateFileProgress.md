---
title: updateFileProgress
description: File is partly downloaded/uploaded
---
## Constructor: updateFileProgress  
[Back to constructors index](index.md)



File is partly downloaded/uploaded

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file\_id|[int](../types/int.md) | Yes|File identifier|
|size|[int](../types/int.md) | Yes|Total file size (0 means unknown)|
|ready|[int](../types/int.md) | Yes|Number of bytes already downloaded/uploaded. Negative number means that download/upload has failed and was terminated|



### Type: [Update](../types/Update.md)


### Example:

```
$updateFileProgress = ['_' => 'updateFileProgress', 'file_id' => int, 'size' => int, 'ready' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateFileProgress", "file_id": int, "size": int, "ready": int}
```


Or, if you're into Lua:  


```
updateFileProgress={_='updateFileProgress', file_id=int, size=int, ready=int}

```


