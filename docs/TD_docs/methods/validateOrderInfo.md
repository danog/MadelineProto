---
title: validateOrderInfo
description: Validates order information provided by the user and returns available shipping options for flexible invoice
---
## Method: validateOrderInfo  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Validates order information provided by the user and returns available shipping options for flexible invoice

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[CLICK ME int53](../types/int53.md) | Yes|Chat identifier of the Invoice message|
|message\_id|[CLICK ME int53](../types/int53.md) | Yes|Message identifier|
|order\_info|[CLICK ME orderInfo](../constructors/orderInfo.md) | Yes|Order information, provided by the user|
|allow\_save|[CLICK ME Bool](../types/Bool.md) | Yes|True, if order information can be saved|


### Return type: [ValidatedOrderInfo](../types/ValidatedOrderInfo.md)

