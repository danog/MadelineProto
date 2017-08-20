---
title: contacts.foreignLinkRequested
description: contacts_foreignLinkRequested attributes, type and example
---
## Constructor: contacts.foreignLinkRequested  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|has\_phone|[Bool](../types/Bool.md) | Yes|



### Type: [contacts\_ForeignLink](../types/contacts_ForeignLink.md)


### Example:

```
$contacts_foreignLinkRequested = ['_' => 'contacts.foreignLinkRequested', 'has_phone' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.foreignLinkRequested", "has_phone": Bool}
```


Or, if you're into Lua:  


```
contacts_foreignLinkRequested={_='contacts.foreignLinkRequested', has_phone=Bool}

```


