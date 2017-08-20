---
title: inputAudioFileLocation
description: inputAudioFileLocation attributes, type and example
---
## Constructor: inputAudioFileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputFileLocation](../types/InputFileLocation.md)


### Example:

```
$inputAudioFileLocation = ['_' => 'inputAudioFileLocation', 'id' => long, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputAudioFileLocation", "id": long, "access_hash": long}
```


Or, if you're into Lua:  


```
inputAudioFileLocation={_='inputAudioFileLocation', id=long, access_hash=long}

```


