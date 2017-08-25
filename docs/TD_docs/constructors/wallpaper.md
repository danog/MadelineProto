---
title: wallpaper
description: Contains information about one wallpaper
---
## Constructor: wallpaper  
[Back to constructors index](index.md)



Contains information about one wallpaper

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Unique persistent wallpaper identifier|
|sizes|Array of [photoSize](../constructors/photoSize.md) | Yes|Available variants of wallpaper of different sizes. These photos can be only downloaded and can't be sent in a message|
|color|[int](../types/int.md) | Yes|Main color of wallpaper in RGB24, should be treated as background color if no photos are specified|



### Type: [Wallpaper](../types/Wallpaper.md)


