# Using methods

A list of all of the methods that can be called with MadelineProto can be found here: [here (layer 75)](https://daniil.it/MadelineProto/API_docs/).

## Peers
If an object of type User, InputUser, Chat, InputChannel, Peer or InputPeer must be provided as a parameter to a method, you can substitute it with the user/group/channel's username (`@username`) or bot API id (`-1029449`, `1249421`, `-100412412901`).  

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'Testing MadelineProto...']);
```

## Secret chats
If an object of type InputSecretChat must be provided as a parameter to a method, you can substitute it with the secret chat's id:

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'Testing MadelineProto...']);
```


## Entities
Methods that allow sending message entities ([messages.sendMessage](http://docs.madelineproto.xyz/API_docs/methods/messages_sendMessage.html) for example) also have an additional `parse_mode` parameter that enables or disables html/markdown parsing of the message to be sent.

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => '[Testing Markdown in MadelineProto](https://docs.madelineproto.xyz)', 'parse_mode' => 'Markdown']);
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => '<a href="https://docs.madelineproto.xyz">Testing HTML in MadelineProto</a>', 'parse_mode' => 'HTML']);
```



## reply_markup
reply_markup accepts bot API reply markup objects as well as MTProto ones.

```
$bot_API_markup = ['inline_keyboard' => [
        ['text' => 'MadelineProto docs', 'url' => 'https://docs.madelineproto.xyz'],
        ['text' => 'MadelineProto channel', 'url' => 'https://t.me/MadelineProto']
    ]
];
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel', 'reply_markup' => $bot_API_markup]);
```


## Bot API objects
To convert the results of methods to bot API objects you must provide a second parameter to method wrappers, containing an array with the `botAPI` key set to true.

```
$bot_API_object = $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel'], ['botAPI' => true]);
```


## No result
To disable fetching the result of a method, the array that must be provided as second parameter to method wrapper must have the `noResponse` key set to true.

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel'], ['noResponse' => true]);
```


