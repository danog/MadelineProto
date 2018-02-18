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
|my\_link|[ContactLink](../types/ContactLink.md) | Yes|
|foreign\_link|[ContactLink](../types/ContactLink.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateContactLink = ['_' => 'updateContactLink', 'user_id' => int, 'my_link' => ContactLink, 'foreign_link' => ContactLink];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateContactLink", "user_id": int, "my_link": ContactLink, "foreign_link": ContactLink}
```


Or, if you're into Lua:  


```
updateContactLink={_='updateContactLink', user_id=int, my_link=ContactLink, foreign_link=ContactLink}

```


