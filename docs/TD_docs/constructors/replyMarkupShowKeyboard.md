---
title: replyMarkupShowKeyboard
description: Contains custom keyboard layout for fast reply to bot
---
## Constructor: replyMarkupShowKeyboard  
[Back to constructors index](index.md)



Contains custom keyboard layout for fast reply to bot

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|rows|Array of [keyboardButton>](../constructors/keyboardButton>.md) | Yes|List of rows of bot keyboard buttons|
|resize\_keyboard|[Bool](../types/Bool.md) | Yes|Do clients need to resize keyboard vertically|
|one\_time|[Bool](../types/Bool.md) | Yes|Do clients need to hide keyboard after use|
|personal|[Bool](../types/Bool.md) | Yes|Keyboard is showed automatically only for mentioned users or replied to user, for incoming messages it is true if and only if keyboard needs to be automatically showed to current user|



### Type: [ReplyMarkup](../types/ReplyMarkup.md)


