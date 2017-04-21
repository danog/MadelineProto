---
title: pageBlockPullquote
description: pageBlockPullquote attributes, type and example
---
## Constructor: pageBlockPullquote  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|text|[RichText](../types/RichText.md) | Yes|
|caption|[RichText](../types/RichText.md) | Yes|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockPullquote = ['_' => 'pageBlockPullquote', 'text' => RichText, 'caption' => RichText, ];
```  

Or, if you're into Lua:  


```
pageBlockPullquote={_='pageBlockPullquote', text=RichText, caption=RichText, }

```


