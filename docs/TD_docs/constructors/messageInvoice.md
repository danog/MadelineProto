---
title: messageInvoice
description: Message with an invoice from a bot
---
## Constructor: messageInvoice  
[Back to constructors index](index.md)



Message with an invoice from a bot

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Goods title|
|description|[string](../types/string.md) | Yes|Goods description|
|photo|[photo](../types/photo.md) | Yes|Goods photo, nullable|
|currency|[string](../types/string.md) | Yes|Currency for goods price|
|total\_amount|[int53](../types/int53.md) | Yes|Goods total price in minimal quantity of the currency|
|start\_parameter|[string](../types/string.md) | Yes|Unique invoice bot start_parameter. To share an invoice use a URL https: t.me/{bot_username}?start={start_parameter}|
|is\_test|[Bool](../types/Bool.md) | Yes|True, if invoice is test|
|need\_shipping\_address|[Bool](../types/Bool.md) | Yes|True, if shipping address should be specified|
|receipt\_message\_id|[int53](../types/int53.md) | Yes|Identifier of message with receipt after the goods are paid|



### Type: [MessageContent](../types/MessageContent.md)


