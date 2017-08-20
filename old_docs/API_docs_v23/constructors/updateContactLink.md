---
title: updateContactLink
description: updateContactLink attributes, type and example
---
## Constructor: updateContactLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|my\_link|[contacts\_MyLink](../types/contacts_MyLink.md) | Yes|
|foreign\_link|[contacts\_ForeignLink](../types/contacts_ForeignLink.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateContactLink = ['_' => 'updateContactLink', 'user_id' => int, 'my_link' => contacts_MyLink, 'foreign_link' => contacts_ForeignLink];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateContactLink", "user_id": int, "my_link": contacts_MyLink, "foreign_link": contacts_ForeignLink}
```


Or, if you're into Lua:  


```
updateContactLink={_='updateContactLink', user_id=int, my_link=contacts_MyLink, foreign_link=contacts_ForeignLink}

```


