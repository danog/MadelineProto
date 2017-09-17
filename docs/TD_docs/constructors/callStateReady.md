---
title: callStateReady
description: Call is ready to use
---
## Constructor: callStateReady  
[Back to constructors index](index.md)



Call is ready to use

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|protocol|[callProtocol](../types/callProtocol.md) | Yes|Call protocols supported by the peer|
|connections|Array of [callConnection](../constructors/callConnection.md) | Yes|Available UDP reflectors|
|config|[string](../types/string.md) | Yes|JSON-encoded call config|
|encryption\_key|[bytes](../types/bytes.md) | Yes|Call encryption key|
|emojis|Array of [string](../constructors/string.md) | Yes|Encryption key emojis fingerprint|



### Type: [CallState](../types/CallState.md)


