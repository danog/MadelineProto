---
title: importChatInviteLink
description: Imports chat invite link, adds current user to a chat if possible. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
---
## Method: importChatInviteLink  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Imports chat invite link, adds current user to a chat if possible. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|invite\_link|[string](../types/string.md) | Yes|Invite link to import. Should begin with "https: t.me/joinchat/", "https: telegram.me/joinchat/" or "https: telegram.dog/joinchat/"|


### Return type: [Ok](../types/Ok.md)

