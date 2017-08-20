---
title: chatReportSpamState
description: Contains information about chat report spam state
---
## Constructor: chatReportSpamState  
[Back to constructors index](index.md)



Contains information about chat report spam state

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|can\_report\_spam|[Bool](../types/Bool.md) | Yes|If true, prompt with "Report spam" action should be shown to the user|



### Type: [ChatReportSpamState](../types/ChatReportSpamState.md)


### Example:

```
$chatReportSpamState = ['_' => 'chatReportSpamState', 'can_report_spam' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatReportSpamState", "can_report_spam": Bool}
```


Or, if you're into Lua:  


```
chatReportSpamState={_='chatReportSpamState', can_report_spam=Bool}

```


