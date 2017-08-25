---
title: addSavedAnimation
description: Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list
---
## Method: addSavedAnimation  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animation|[InputFile](../types/InputFile.md) | Yes|Animation file to add. Only known to server animations (i. e. successfully sent via message) can be added to the list|


### Return type: [Ok](../types/Ok.md)

