---
title: game
description: Describes a game
---
## Constructor: game  
[Back to constructors index](index.md)



Describes a game

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Game id|
|short\_name|[string](../types/string.md) | Yes|Game short name, to share a game use a URL https: t.me/{bot_username}?game={game_short_name}|
|title|[string](../types/string.md) | Yes|Game title|
|text|[string](../types/string.md) | Yes|Game text, usually containing game scoreboards|
|text\_entities|Array of [textEntity](../constructors/textEntity.md) | Yes|Entities contained in the text|
|description|[string](../types/string.md) | Yes|Game description|
|photo|[photo](../types/photo.md) | Yes|Game photo|
|animation|[animation](../types/animation.md) | Yes|Game animation, nullable|



### Type: [Game](../types/Game.md)


