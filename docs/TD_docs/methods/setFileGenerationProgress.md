---
title: setFileGenerationProgress
description: Next part of a file was generated
---
## Method: setFileGenerationProgress  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Next part of a file was generated

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|generation\_id|[long](../types/long.md) | Yes|Identifier of the generation process|
|size|[int](../types/int.md) | Yes|Full size of file in bytes, 0 if unknown.|
|local\_size|[int](../types/int.md) | Yes|Number of bytes already generated. Negative number means that generation has failed and should be terminated|


### Return type: [Ok](../types/Ok.md)

