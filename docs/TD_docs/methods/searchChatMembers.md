---
title: searchChatMembers
description: Searches for the specified query in the first name, last name and username among members of the specified chat. Requires administrator rights in broadcast channels
---
## Method: searchChatMembers  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for the specified query in the first name, last name and username among members of the specified chat. Requires administrator rights in broadcast channels

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|query|[string](../types/string.md) | Yes|Query to search for|
|limit|[int](../types/int.md) | Yes|Maximum number of users to be returned|


### Return type: [ChatMembers](../types/ChatMembers.md)

