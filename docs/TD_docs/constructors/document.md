---
title: document
description: Describes document of any type
---
## Constructor: document  
[Back to constructors index](index.md)



Describes document of any type

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file\_name|[string](../types/string.md) | Yes|Original name of a file as defined by sender|
|mime\_type|[string](../types/string.md) | Yes|MIME type of file as defined by sender|
|thumb|[photoSize](../types/photoSize.md) | Yes|Document thumb as defined by sender, nullable|
|document|[file](../types/file.md) | Yes|File with document|



### Type: [Document](../types/Document.md)


### Example:

```
$document = ['_' => 'document', 'file_name' => 'string', 'mime_type' => 'string', 'thumb' => photoSize, 'document' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "document", "file_name": "string", "mime_type": "string", "thumb": photoSize, "document": file}
```


Or, if you're into Lua:  


```
document={_='document', file_name='string', mime_type='string', thumb=photoSize, document=file}

```


