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
|sizes|Array of [photoSize](../constructors/photoSize.md) | Yes|Available variants of wallpaper of different sizes. These photos can be only downloaded and can't be sent in a message|
|color|[int](../types/int.md) | Yes|Main color of wallpaper in RGB24, should be treated as background color if no photos are specified|



### Type: [Wallpaper](../types/Wallpaper.md)


### Example:

```
$wallpaper = ['_' => 'wallpaper', 'sizes' => [photoSize], 'color' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "wallpaper", "sizes": [photoSize], "color": int}
```


Or, if you're into Lua:  


```
wallpaper={_='wallpaper', sizes={photoSize}, color=int}

```


