---
title: updateGroup
description: Some data about a group has been changed
---
## Constructor: updateGroup  
[Back to constructors index](index.md)



Some data about a group has been changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|group|[group](../types/group.md) | Yes|New data about the group|



### Type: [Update](../types/Update.md)


### Example:

```
$updateGroup = ['_' => 'updateGroup', 'group' => group];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateGroup", "group": group}
```


Or, if you're into Lua:  


```
updateGroup={_='updateGroup', group=group}

```


