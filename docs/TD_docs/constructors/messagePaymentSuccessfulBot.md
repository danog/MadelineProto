---
title: messagePaymentSuccessfulBot
description: Bots only. Payment completed
---
## Constructor: messagePaymentSuccessfulBot  
[Back to constructors index](index.md)



Bots only. Payment completed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|currency|[string](../types/string.md) | Yes|Currency for goods price|
|total\_amount|[int53](../types/int53.md) | Yes|Goods total price in minimal quantity of the currency|
|invoice\_payload|[bytes](../types/bytes.md) | Yes|Invoice payload|
|shipping\_option\_id|[string](../types/string.md) | Yes|Identifier of a choosed by user shipping option, may be empty if not applicable|
|order\_info|[orderInfo](../types/orderInfo.md) | Yes|Information about the order, nullable|
|telegram\_payment\_charge\_id|[string](../types/string.md) | Yes|Telegram payment identifier|
|provider\_payment\_charge\_id|[string](../types/string.md) | Yes|Provider payment identifier|



### Type: [MessageContent](../types/MessageContent.md)


