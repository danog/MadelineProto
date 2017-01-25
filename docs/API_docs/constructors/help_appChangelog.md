---
title: help.appChangelog
description: help_appChangelog attributes, type and example
---
## Constructor: help.appChangelog  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Required|



### Type: [help\_AppChangelog](../types/help_AppChangelog.md)


### Example:

```
$help_appChangelog = ['_' => 'help.appChangelog', 'message' => string, 'media' => MessageMedia, 'entities' => [Vector t], ];
```  

