---
title: updateAuthState
description: User authorization state has changed
---
## Constructor: updateAuthState  
[Back to constructors index](index.md)



User authorization state has changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|auth\_state|[AuthState](../types/AuthState.md) | Yes|New authorization state|



### Type: [Update](../types/Update.md)


### Example:

```
$updateAuthState = ['_' => 'updateAuthState', 'auth_state' => AuthState];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateAuthState", "auth_state": AuthState}
```


Or, if you're into Lua:  


```
updateAuthState={_='updateAuthState', auth_state=AuthState}

```


