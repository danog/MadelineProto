---
title: updateFileGenerationStart
description: File generation process need to be started by the client
---
## Constructor: updateFileGenerationStart  
[Back to constructors index](index.md)



File generation process need to be started by the client

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|generation\_id|[long](../types/long.md) | Yes|Unique identifier for the generation process|
|original\_path|[string](../types/string.md) | Yes|Path to a file from which new file is generated, may be empty|
|destination\_path|[string](../types/string.md) | Yes|Path to a file which should be created and to which new file should be generated|
|conversion|[string](../types/string.md) | Yes|String specifying conversion applied to the original file|



### Type: [Update](../types/Update.md)


