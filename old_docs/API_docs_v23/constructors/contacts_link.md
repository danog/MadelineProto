---
title: contacts.link
description: contacts_link attributes, type and example
---
## Constructor: contacts.link  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|my\_link|[contacts\_MyLink](../types/contacts_MyLink.md) | Yes|
|foreign\_link|[contacts\_ForeignLink](../types/contacts_ForeignLink.md) | Yes|
|user|[User](../types/User.md) | Yes|



### Type: [contacts\_Link](../types/contacts_Link.md)


### Example:

```
$contacts_link = ['_' => 'contacts.link', 'my_link' => contacts_MyLink, 'foreign_link' => contacts_ForeignLink, 'user' => User];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.link", "my_link": contacts_MyLink, "foreign_link": contacts_ForeignLink, "user": User}
```


Or, if you're into Lua:  


```
contacts_link={_='contacts.link', my_link=contacts_MyLink, foreign_link=contacts_ForeignLink, user=User}

```


