---
title: messages.sentEncryptedFile
description: messages_sentEncryptedFile attributes, type and example
---
## Constructor: messages.sentEncryptedFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|
|file|[EncryptedFile](../types/EncryptedFile.md) | Yes|



### Type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)


### Example:

```
$messages_sentEncryptedFile = ['_' => 'messages.sentEncryptedFile', 'date' => int, 'file' => EncryptedFile];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.sentEncryptedFile", "date": int, "file": EncryptedFile}
```


Or, if you're into Lua:  


```
messages_sentEncryptedFile={_='messages.sentEncryptedFile', date=int, file=EncryptedFile}

```


