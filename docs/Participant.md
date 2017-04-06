---
title: Participant
description: PWRTelegram participant attributes, type and example
---
## Constructor: PWRTelegram chat participant  



### Attributes:

| Name     |    Type       | Required | Description|
|----------|:-------------:|:--------:|-----------:|
|user|[Chat](Chat.md) | Yes| The participant|
|inviter|[Chat](Chat.md) | Optional|The user that invited this participant|
|date|[int](https://daniil.it/MadelineProto/API_docs/types/int.md) | Yes|When was the user invited|
|role|[string](https://daniil.it/MadelineProto/API_docs/types/int.md) | Yes|user, admin, creator, moderator, editor, creator, kicked|

