---
title: contact
description: contact attributes, type and example
---
## Constructor: contact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|mutual|[Bool](../types/Bool.md) | Yes|



### Type: [Contact](../types/Contact.md)


### Example:

```
$contact = ['_' => 'contact', 'user_id' => int, 'mutual' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contact", "user_id": int, "mutual": Bool}
```


Or, if you're into Lua:  


```
contact={_='contact', user_id=int, mutual=Bool}

```


