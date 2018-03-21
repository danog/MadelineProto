# Inline buttons

You can easily click inline buttons using MadelineProto, just access the correct button:

```php
class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function onUpdateNewChannelMessage($update)
    {
        $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
        
        if (isset($update['message']['reply_markup']['rows'])) {
            foreach ($update['message']['reply_markup']['rows'] as $row) {
                foreach ($row['buttons'] as $button) {
                    $button->click();
                }
            }
        }
        
    }
}


$settings = ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e'], 'updates' => ['handle_updates' => true]];

$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);

$MadelineProto->start();
$MadelineProto->setEventHandler('\EventHandler');
$MadelineProto->loop();
```

This peice of code will automatically click all buttons in all keyboards sent in any chat.

You can then access properties of `$button` (they vary depending on the [type of button](https://docs.madelineproto.xyz/API_docs/types/KeyboardButton.html)):

```php
$text = $button['text'];
```

And click them:

```php
$button->click();
```








<form action="https://docs.madelineproto.xyz/docs/DIALOGS.html"><input type="submit" value="Previous section" /></form><form action="https://docs.madelineproto.xyz/docs/CALLS.html"><input type="submit" value="Next section" /></form>