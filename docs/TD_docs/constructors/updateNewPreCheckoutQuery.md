---
title: updateNewPreCheckoutQuery
description: Bots only. New incoming pre-checkout query. Contains full information about checkout
---
## Constructor: updateNewPreCheckoutQuery  
[Back to constructors index](index.md)



Bots only. New incoming pre-checkout query. Contains full information about checkout

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Unique query identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|currency|[string](../types/string.md) | Yes|Currency for goods price|
|total\_amount|[int53](../types/int53.md) | Yes|Goods total price in minimal quantity of the currency|
|invoice\_payload|[bytes](../types/bytes.md) | Yes|Invoice payload|
|shipping\_option\_id|[string](../types/string.md) | Yes|Identifier of a choosed by user shipping option, may be empty if not applicable|
|order\_info|[orderInfo](../types/orderInfo.md) | Yes|Information about the order, nullable|



### Type: [Update](../types/Update.md)


