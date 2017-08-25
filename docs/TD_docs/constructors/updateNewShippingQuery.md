---
title: updateNewShippingQuery
description: Bots only. New incoming shipping query. Only for invoices with flexible price
---
## Constructor: updateNewShippingQuery  
[Back to constructors index](index.md)



Bots only. New incoming shipping query. Only for invoices with flexible price

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Unique query identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|invoice\_payload|[string](../types/string.md) | Yes|Invoice payload|
|shipping\_address|[shippingAddress](../types/shippingAddress.md) | Yes|User shipping address|



### Type: [Update](../types/Update.md)


