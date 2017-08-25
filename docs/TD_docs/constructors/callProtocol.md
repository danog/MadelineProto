---
title: callProtocol
description: Specifies supported call protocols
---
## Constructor: callProtocol  
[Back to constructors index](index.md)



Specifies supported call protocols

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|udp\_p2p|[Bool](../types/Bool.md) | Yes|True, if UDP peer to peer connections are supported|
|udp\_reflector|[Bool](../types/Bool.md) | Yes|True, if connection through UDP reflectors are supported|
|min\_layer|[int](../types/int.md) | Yes|Minimum supported layer, use 65|
|max\_layer|[int](../types/int.md) | Yes|Maximum supported layer, use 65|



### Type: [CallProtocol](../types/CallProtocol.md)


