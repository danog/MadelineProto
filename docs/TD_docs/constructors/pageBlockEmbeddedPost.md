---
title: pageBlockEmbeddedPost
description: Embedded post
---
## Constructor: pageBlockEmbeddedPost  
[Back to constructors index](index.md)



Embedded post

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|url|[string](../types/string.md) | Yes|Web page URL|
|author|[string](../types/string.md) | Yes|Post author|
|author\_photo|[photo](../types/photo.md) | Yes|Post author photo|
|date|[int](../types/int.md) | Yes|Post date, unix time. 0 if unknown|
|page\_blocks|Array of [PageBlock](../constructors/PageBlock.md) | Yes|Post content|
|caption|[RichText](../types/RichText.md) | Yes|Post caption|



### Type: [PageBlock](../types/PageBlock.md)


