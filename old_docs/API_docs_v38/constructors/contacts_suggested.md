---
title: contacts.suggested
description: contacts_suggested attributes, type and example
---
## Constructor: contacts.suggested  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|results|Array of [ContactSuggested](../types/ContactSuggested.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Suggested](../types/contacts_Suggested.md)


### Example:

```
$contacts_suggested = ['_' => 'contacts.suggested', 'results' => [ContactSuggested], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.suggested", "results": [ContactSuggested], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_suggested={_='contacts.suggested', results={ContactSuggested}, users={User}}

```


