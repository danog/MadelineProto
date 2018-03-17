# Handling updates

Update handling can be done in two ways: long polling (getUpdates) or callback (pseudo-webhook).


## callback

```
class UpdateHandler
{
    public static $MadelineProto;
    public static function handle_update($update) {
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }
                $res = json_encode($update, JSON_PRETTY_PRINT);
                if ($res == '') {
                    $res = var_export($update, true);
                }

                try {
                    self::$MadelineProto->messages->sendMessage(['peer' => $update['update']['_'] === 'updateNewMessage' ? $update['update']['message']['from_id'] : $update['update']['message']['to_id'], 'message' => $res, 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    self::$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
        }

    }
}
UpdateHandler::$MadelineProto = $MadelineProto;
$MadelineProto->settings['updates']['handle_updates'] = true;
$MadelineProto->settings['updates']['callback'] = ['UpdateHandler', 'handle_update'];
while (true) {
    $MadelineProto->get_updates_difference();
}
```

When an update is received, if the `handle_updates` [setting](SETTINGS.md) is equal to true, the update callback function passed in the `callback` [setting](SETTINGS.md) is called.

The callback function accepts one parameter with an [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html) object.

To force internal update fetching, the `get_updates_difference` function (or any other MadelineProto function) should be called.

For greater stability, long polling is recommended instead of callbacks.


## Long polling
```
$MadelineProto->settings['updates']['handle_updates'] = true;
$MadelineProto->settings['updates']['callback'] = 'get_updates_update_handler';
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    \danog\MadelineProto\Logger::log($updates);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }
                $res = json_encode($update, JSON_PRETTY_PRINT);
                if ($res == '') {
                    $res = var_export($update, true);
                }

                try {
                    $MadelineProto->messages->sendMessage(['peer' => $update['update']['_'] === 'updateNewMessage' ? $update['update']['message']['from_id'] : $update['update']['message']['to_id'], 'message' => $res, 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
        }
    }
}
```

If the callback is equal to `get_updates_update_handler`, all incoming updates will be stored into an array (its size limit is specified by the updates\_array\_limit parameter in the [settings](SETTINGS.md)) and can be fetched by running the `get_updates` method.  

The get_updates function accepts an array of options as the first parameter, and returns an array of updates (an array containing the update id and an object of type [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html)).  


IMPORTANT: Note that you should turn off update handling if you don't plan to use it because the default get_updates update handling stores updates in an array inside the MadelineProto object, without deleting old ones unless they are read using get_updates.  

This will eventually fill up the RAM of your server if you don't disable updates or read them using get_updates.  

To clear the update buffer after disabling update handling use this snippet:

```
$MadelineProto->API->updates = [];
```
