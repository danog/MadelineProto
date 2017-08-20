---
title: messageContact
description: User contact message
---
## Constructor: messageContact  
[Back to constructors index](index.md)



User contact message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contact|[contact](../types/contact.md) | Yes|Message content|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageContact = ['_' => 'messageContact', 'contact' => contact];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageContact", "contact": contact}
```


Or, if you're into Lua:  


```
messageContact={_='messageContact', contact=contact}

```


