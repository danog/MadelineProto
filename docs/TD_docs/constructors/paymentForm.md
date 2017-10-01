---
title: paymentForm
description: Information about invoice payment form
---
## Constructor: paymentForm  
[Back to constructors index](index.md)



Information about invoice payment form

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|invoice|[invoice](../constructors/invoice.md) | Yes|Full information about the invoice|
|url|[string](../types/string.md) | Yes|Payment form URL|
|payments\_provider|[paymentsProviderStripe](../constructors/paymentsProviderStripe.md) | Yes|Information about payment provider if available, to support it natively without opening the URL, nullable|
|saved\_order\_info|[orderInfo](../constructors/orderInfo.md) | Yes|Saved server-side order information, nullable|
|saved\_credentials|[savedCredentials](../constructors/savedCredentials.md) | Yes|Information about saved card credentials, nullable|
|can\_save\_credentials|[Bool](../types/Bool.md) | Yes|True, if the user can choose to save credentials|
|need\_password|[Bool](../types/Bool.md) | Yes|True, if the user will be able to save credentials if he set up a password|



### Type: [PaymentForm](../types/PaymentForm.md)


