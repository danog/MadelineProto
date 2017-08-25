---
title: secretChat
description: Represents a secret chat
---
## Constructor: secretChat  
[Back to constructors index](index.md)



Represents a secret chat

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Secret chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of the interlocutor|
|state|[int](../types/int.md) | Yes|State of the secret chat, 0 - yet not created, 1 - active, 2 - closed|
|is\_outbound|[Bool](../types/Bool.md) | Yes|True if chat was created by the current logged in user, false otherwise|
|ttl|[int](../types/int.md) | Yes|Current message TTL setting for the chat in seconds|
|key\_hash|[bytes](../types/bytes.md) | Yes|Hash of the current used key for comparison with the hash of the interlocutor's key. String of 36 bytes, which should be used to make a 12x12 square image with a color depth of 4. First 16 bytes should be used to make a central 8 * 8 square, left 20 bytes should be used to construct a border of width 2 around that square. Alternatively first 32 bytes of the hash can be converted to hex and printed as 32 2-digit hex numbers|
|layer|[int](../types/int.md) | Yes|Secret chat layer, determining features supported by other client. Video notes are supported if layer >= 66|



### Type: [SecretChat](../types/SecretChat.md)


