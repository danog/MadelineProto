---
title: peerSettings
description: peerSettings attributes, type and example
---
## Constructor: peerSettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|report\_spam|[Bool](../types/Bool.md) | Optional|



### Type: [PeerSettings](../types/PeerSettings.md)


### Example:

```
$peerSettings = ['_' => 'peerSettings', 'report_spam' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "peerSettings", "report_spam": Bool}
```


Or, if you're into Lua:  


```
peerSettings={_='peerSettings', report_spam=Bool}

```


