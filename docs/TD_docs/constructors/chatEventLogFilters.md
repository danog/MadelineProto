---
title: chatEventLogFilters
description: Represents a set of filters used to obtain a chat event log
---
## Constructor: chatEventLogFilters  
[Back to constructors index](index.md)



Represents a set of filters used to obtain a chat event log

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message\_edits|[Bool](../types/Bool.md) | Yes|True, if message edits should be returned|
|message\_deletions|[Bool](../types/Bool.md) | Yes|True, if message deletions should be returned|
|message\_pins|[Bool](../types/Bool.md) | Yes|True, if message pins should be returned|
|member\_joins|[Bool](../types/Bool.md) | Yes|True, if chat member joins should be returned|
|member\_leaves|[Bool](../types/Bool.md) | Yes|True, if chat member leaves should be returned|
|member\_invites|[Bool](../types/Bool.md) | Yes|True, if chat member invites should be returned|
|member\_promotions|[Bool](../types/Bool.md) | Yes|True, if chat member promotions/demotions should be returned|
|member\_restrictions|[Bool](../types/Bool.md) | Yes|True, if chat member restrictions/unrestrictions including bans/unbans should be returned|
|info\_changes|[Bool](../types/Bool.md) | Yes|True, if changes of chat information should be returned|
|setting\_changes|[Bool](../types/Bool.md) | Yes|True, if changes of chat settings should be returned|



### Type: [ChatEventLogFilters](../types/ChatEventLogFilters.md)


