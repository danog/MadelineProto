---
title: updateFile
description: File is downloaded/uploaded
---
## Constructor: updateFile  
[Back to constructors index](index.md)



File is downloaded/uploaded

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file|[file](../types/file.md) | Yes|Synced file|



### Type: [Update](../types/Update.md)


### Example:

```
$updateFile = ['_' => 'updateFile', 'file' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateFile", "file": file}
```


Or, if you're into Lua:  


```
updateFile={_='updateFile', file=file}

```


