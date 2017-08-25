---
title: inputMessageVoice
description: Voice message
---
## Constructor: inputMessageVoice  
[Back to constructors index](index.md)



Voice message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|voice|[InputFile](../types/InputFile.md) | Yes|Voice file to send|
|duration|[int](../types/int.md) | Yes|Duration of the voice in seconds|
|waveform|[bytes](../types/bytes.md) | Yes|Waveform representation of the voice in 5-bit format|
|caption|[string](../types/string.md) | Yes|Voice caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


