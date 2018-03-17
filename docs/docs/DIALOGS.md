# Getting all chats

There are two ways to get a list of all chats, depending if you logged in as a user, or as a bot.

## User: get_dialogs
```
$dialogs = $MadelineProto->get_dialogs();
foreach ($dialogs as $peer) {
    $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => 'Hi! Testing MadelineProto broadcasting!']);
}
```

`get_dialogs` will return a full list of all chats you're member of, see [here for the parameters and the result](https://docs.madelineproto.xyz/get_dialogs.html)

## Bot: internal peer database
```
foreach ($MadelineProto->API->chats as $bot_api_id => $chat) {
    try {
        $MadelineProto->messages->sendMessage(['peer' => $chat, 'message' => "Hi $bot_api_id! Testing MadelineProto broadcasting!"]);
    } catch (\danog\MadelineProto\RPCErrorException $e) {
        echo $e;
    }
}
```

Since bots cannot run `get_dialogs`, you must make use of the internal MadelineProto database to get a list of all users, chats and channels MadelineProto has seen.
`$MadelineProto->API->chats` contains a list of [Chat](../API_docs/types/Chat.md) and [User](../API_docs/types/User.md) objects, indexed by bot API id.