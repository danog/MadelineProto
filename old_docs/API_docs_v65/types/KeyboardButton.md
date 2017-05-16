---
title: KeyboardButton
description: constructors and methods of type KeyboardButton
---
## Type: KeyboardButton  
[Back to types index](index.md)



Clicking these buttons:

To click these buttons simply run the `click` method:  

```
$result = $KeyboardButton->click();
```

`$result` can be one of the following:


* A string - If the button is a keyboardButtonUrl

* [Updates](Updates.md) - If the button is a keyboardButton, the message will be sent to the chat, in reply to the message with the keyboard

* [messages_BotCallbackAnswer](messages_BotCallbackAnswer.md) - If the button is a keyboardButtonCallback or a keyboardButtonGame the button will be pressed and the result will be returned

* `false` - If the button is an unsupported button, like keyboardButtonRequestPhone, keyboardButtonRequestGeoLocation, keyboardButtonSwitchInlinekeyboardButtonBuy; you will have to parse data from these buttons manually


### Possible values (constructors):

[keyboardButton](../constructors/keyboardButton.md)  

[keyboardButtonUrl](../constructors/keyboardButtonUrl.md)  

[keyboardButtonCallback](../constructors/keyboardButtonCallback.md)  

[keyboardButtonRequestPhone](../constructors/keyboardButtonRequestPhone.md)  

[keyboardButtonRequestGeoLocation](../constructors/keyboardButtonRequestGeoLocation.md)  

[keyboardButtonSwitchInline](../constructors/keyboardButtonSwitchInline.md)  

[keyboardButtonGame](../constructors/keyboardButtonGame.md)  

[keyboardButtonBuy](../constructors/keyboardButtonBuy.md)  



### Methods that return an object of this type (methods):



