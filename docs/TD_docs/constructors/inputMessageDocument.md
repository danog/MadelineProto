---
title: inputMessageDocument
description: Document message
---
## Constructor: inputMessageDocument  
[Back to constructors index](index.md)



Document message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|document|[InputFile](../types/InputFile.md) | Yes|Document to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Document thumb, if available|
|caption|[string](../types/string.md) | Yes|Document caption, 0-200 characters|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageDocument = ['_' => 'inputMessageDocument', 'document' => InputFile, 'thumb' => InputThumb, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageDocument", "document": InputFile, "thumb": InputThumb, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMessageDocument={_='inputMessageDocument', document=InputFile, thumb=InputThumb, caption='string'}

```


