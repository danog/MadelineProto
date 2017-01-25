---
title: updateChannelWebPage
description: updateChannelWebPage attributes, type and example
---
## Constructor: updateChannelWebPage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|webpage|[WebPage](../types/WebPage.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelWebPage = ['_' => 'updateChannelWebPage', 'channel_id' => int, 'webpage' => WebPage, 'pts' => int, 'pts_count' => int, ];
```  

