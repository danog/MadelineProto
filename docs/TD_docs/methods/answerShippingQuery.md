---
title: answerShippingQuery
description: Bots only. Sets result of a shipping query
---
## Method: answerShippingQuery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Sets result of a shipping query

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|shipping\_query\_id|[long](../types/long.md) | Yes|Identifier of the shipping query|
|shipping\_options|Array of [shippingOption](../types/shippingOption.md) | Yes|Available shipping options|
|error\_message|[string](../types/string.md) | Yes|Error message, empty on success|


### Return type: [Ok](../types/Ok.md)

