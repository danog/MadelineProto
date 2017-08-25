---
title: inputMessageInvoice
description: Message with an invoice, can be used only by bots and in private chats only
---
## Constructor: inputMessageInvoice  
[Back to constructors index](index.md)



Message with an invoice, can be used only by bots and in private chats only

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|invoice|[invoice](../types/invoice.md) | Yes|The invoice|
|title|[string](../types/string.md) | Yes|Product title, 1-32 characters|
|description|[string](../types/string.md) | Yes|Product description, 0-255 characters|
|photo\_url|[string](../types/string.md) | Yes|Goods photo URL, optional|
|photo\_size|[int](../types/int.md) | Yes|Goods photo size|
|photo\_width|[int](../types/int.md) | Yes|Goods photo width|
|photo\_height|[int](../types/int.md) | Yes|Goods photo height|
|payload|[bytes](../types/bytes.md) | Yes|Invoice payload|
|provider\_token|[string](../types/string.md) | Yes|Payments provider token|
|start\_parameter|[string](../types/string.md) | Yes|Unique invoice bot start_parameter for generation of this invoice|



### Type: [InputMessageContent](../types/InputMessageContent.md)


