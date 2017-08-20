---
title: sessions
description: Contains list of sessions
---
## Constructor: sessions  
[Back to constructors index](index.md)



Contains list of sessions

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sessions|Array of [session](../constructors/session.md) | Yes|List of sessions|



### Type: [Sessions](../types/Sessions.md)


### Example:

```
$sessions = ['_' => 'sessions', 'sessions' => [session]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sessions", "sessions": [session]}
```


Or, if you're into Lua:  


```
sessions={_='sessions', sessions={session}}

```


