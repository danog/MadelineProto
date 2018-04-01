# Secret chats

MadelineProto provides wrappers to work with secret chats.

* [Requesting secret chats](#requesting-secret-chats)
* [Accepting secret chats](#accepting-secret-chats)
* [Checking secret chat status](#checking-secret-chat-status)
* [Sending secret messages](#sending-secret-messages)

## Requesting secret chats

```php
$secret_chat = $MadelineProto->request_secret_chat($InputUser);
```

[`request_secret_chat`](https://docs.madelineproto.xyz/request_secret_chat.html) requests a secret secret chat to the [InputUser](https://docs.madelineproto.xyz/API_docs/types/InputUser.html), ID, or username specified, and returns the secret chat ID.


## Accepting secret chats

Secret chats are accepted or refused automatically, based on a value in the [settings](SETTINGS.html#settingssecret_chatsaccept_chats) (by default MadelineProto is set to accept all secret chats).

Before sending any message, you must check if the secret chat was accepted by the other client with the following method:

## Checking secret chat status

```php
$status = $MadelineProto->secret_chat_status($chat);
```

$status is 0 if the chat cannot be found in the local database, 1 if the chat was requested but not yet accepted, and 2 if it is a valid accepted secret chat.

## Sending secret messages

[Full example](https://github.com/danog/MadelineProto/blob/master/secret_bot.php)

To send messages/files/service messages, simply use the sendEncrypted methods with objects that use the same layer used by the other client (specified by the number after the underscore in decryptedMessage object names, to obtain the layer that must be used for a secret chat use the following wrapper method).  

```php
$secret_chat = $MadelineProto->get_secret_chat($chat);
/*
[
    'key' => [ // The authorization key
        'auth_key' => 'string', // 256 bytes long
        'fingerprint' => 10387574747492, // a 64 bit signed integer
        'visualization_orig' => 'string', // 16 bytes long
        'visualization_46' => 'string', // 20 bytes long
         // The two visualization strings must be concatenated to generate a visual fingerprint
    ],
    'admin' => false, // Am I the creator of the chat?
    'user_id' => 101374607, // The user id of the other user
    'InputEncryptedChat' => [...], // An inputEncryptedChat object that represents the current chat
    'in_seq_no_x' => number, // in_seq_no must be multiplied by two and incremented by this before being sent over the network
    'out_seq_no_x' => number, // out_seq_no must be multiplied by two and incremeneted this begore being sent over the network
    'layer' => number, // The secret chat TL layer used by the other client
    'ttl' => number, // The default time to live of messages in this chat
    'ttr' => 100, // Time left before rekeying must be done, decremented by one every time a message as encrypted/decrypted with this key
    'updated' => time(), // Last time the key of the current chat was changed
    'incoming' => [], // Incoming messages, TL serialized strings
    'outgoing' => [], // Outgoing messages, TL serialized strings
    'created' => time(), // When was this chat created
    'rekeying' => [0] // Info for rekeying
];
*/
```

This method gets info about a certain chat.

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/CALLS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/LUA.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>