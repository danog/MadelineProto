---
title: inputMessageContact
description: User contact message
---
## Constructor: inputMessageContact  
[Back to constructors index](index.md)



User contact message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contact|[contact](../types/contact.md) | Yes|Contact to send|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageContact = ['_' => 'inputMessageContact', 'contact' => contact];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageContact", "contact": contact}
```


Or, if you're into Lua:  


```
inputMessageContact={_='inputMessageContact', contact=contact}

```


