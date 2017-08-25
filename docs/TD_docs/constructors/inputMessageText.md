---
title: inputMessageText
description: Text message
---
## Constructor: inputMessageText  
[Back to constructors index](index.md)



Text message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[string](../types/string.md) | Yes|Text to send|
|disable\_web\_page\_preview|[Bool](../types/Bool.md) | Yes|Pass true to disable rich preview for link in the message text|
|clear\_draft|[Bool](../types/Bool.md) | Yes|Pass true if chat draft message should be deleted|
|entities|Array of [textEntity](../constructors/textEntity.md) | Yes|Bold, Italic, Code, Pre, PreCode and TextUrl entities contained in the text. Non-bot users can't use TextUrl entities. Can't be used with non-null parse_mode|
|parse\_mode|[TextParseMode](../types/TextParseMode.md) | Yes|Text parse mode, nullable. Can't be used along with enitities|



### Type: [InputMessageContent](../types/InputMessageContent.md)


