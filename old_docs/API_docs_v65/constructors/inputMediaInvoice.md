---
title: inputMediaInvoice
description: inputMediaInvoice attributes, type and example
---
## Constructor: inputMediaInvoice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|
|description|[string](../types/string.md) | Yes|
|photo|[InputWebDocument](../types/InputWebDocument.md) | Optional|
|invoice|[Invoice](../types/Invoice.md) | Yes|
|payload|[bytes](../types/bytes.md) | Yes|
|provider|[string](../types/string.md) | Yes|
|start\_param|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaInvoice = ['_' => 'inputMediaInvoice', 'title' => 'string', 'description' => 'string', 'photo' => InputWebDocument, 'invoice' => Invoice, 'payload' => 'bytes', 'provider' => 'string', 'start_param' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaInvoice", "title": "string", "description": "string", "photo": InputWebDocument, "invoice": Invoice, "payload": "bytes", "provider": "string", "start_param": "string"}
```


Or, if you're into Lua:  


```
inputMediaInvoice={_='inputMediaInvoice', title='string', description='string', photo=InputWebDocument, invoice=Invoice, payload='bytes', provider='string', start_param='string'}

```


