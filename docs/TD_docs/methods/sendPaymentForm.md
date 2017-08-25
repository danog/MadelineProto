---
title: sendPaymentForm
description: Sends filled payment form to the bot for the final verification
---
## Method: sendPaymentForm  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends filled payment form to the bot for the final verification

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier of the Invoice message|
|message\_id|[int53](../types/int53.md) | Yes|Message identifier|
|order\_info\_id|[string](../types/string.md) | Yes|Identifier returned by ValidateOrderInfo or empty string|
|shipping\_option\_id|[string](../types/string.md) | Yes|Identifier of a chosen shipping option, if applicable|
|credentials|[InputCredentials](../types/InputCredentials.md) | Yes|Credentials choosed by user for payment|


### Return type: [PaymentResult](../types/PaymentResult.md)

