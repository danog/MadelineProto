# Inline buttons

You can easily click inline buttons using MadelineProto, just access the correct button:

```
$button = $update['update']['message']['reply_markup']['rows'][0]['buttons'][0];
```

You can then access properties (they vary depending on the [type of button](https://docs.madelineproto.xyz/API_docs/types/KeyboardButton.html)):

```
$text = $button['text'];
```

And click them:

```
$button->click();
```

