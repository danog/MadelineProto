---
title: paymentResult
description: Contains result of a payment query
---
## Constructor: paymentResult  
[Back to constructors index](index.md)



Contains result of a payment query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|success|[Bool](../types/Bool.md) | Yes|True, if payment request was successful. If false, verification_url will be not empty|
|verification\_url|[string](../types/string.md) | Yes|Url for additional payments credentials verification|



### Type: [PaymentResult](../types/PaymentResult.md)


