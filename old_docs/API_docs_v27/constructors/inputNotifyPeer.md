---
title: inputNotifyPeer
description: inputNotifyPeer attributes, type and example
---
## Constructor: inputNotifyPeer  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|



### Type: [InputNotifyPeer](../types/InputNotifyPeer.md)


### Example:

```
$inputNotifyPeer = ['_' => 'inputNotifyPeer', 'peer' => InputPeer];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputNotifyPeer", "peer": InputPeer}
```


Or, if you're into Lua:  


```
inputNotifyPeer={_='inputNotifyPeer', peer=InputPeer}

```


