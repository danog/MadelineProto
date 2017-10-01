---
title: paymentReceipt
description: Contains information about successful payment
---
## Constructor: paymentReceipt  
[Back to constructors index](index.md)



Contains information about successful payment

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|date|[int](../types/int.md) | Yes|Payment date, unix time|
|payments\_provider\_user\_id|[int](../types/int.md) | Yes|User identifier of payments provider bot|
|invoice|[invoice](../constructors/invoice.md) | Yes|Information about the invoice|
|order\_info|[orderInfo](../constructors/orderInfo.md) | Yes|Order information, nullable|
|shipping\_option|[shippingOption](../constructors/shippingOption.md) | Yes|Chosen shipping option, nullable|
|credentials\_title|[string](../types/string.md) | Yes|Title of the saved credentials|



### Type: [PaymentReceipt](../types/PaymentReceipt.md)


