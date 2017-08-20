---
title: inputNotifyGeoChatPeer
description: inputNotifyGeoChatPeer attributes, type and example
---
## Constructor: inputNotifyGeoChatPeer  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|



### Type: [InputNotifyPeer](../types/InputNotifyPeer.md)


### Example:

```
$inputNotifyGeoChatPeer = ['_' => 'inputNotifyGeoChatPeer', 'peer' => InputGeoChat];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputNotifyGeoChatPeer", "peer": InputGeoChat}
```


Or, if you're into Lua:  


```
inputNotifyGeoChatPeer={_='inputNotifyGeoChatPeer', peer=InputGeoChat}

```


