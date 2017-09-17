# MadelineProto, a PHP MTProto telegram client

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto)!

This library can be used to create php telegram bots (like bot API bots, only better) and php telegram userbots (like tg-cli userbots, only better).

This library can also be used to create lua telegram bots (like bot API bots, only better) and lua telegram userbots (like tg-cli userbots, only better).

## Index: 

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Examples](#examples)
- [Methods](#methods)
- [Uploading and downloading files](#uploading-and-downloading-files)
- [Usage](#usage)
- [Settings](#settings)
- [Update management (getting incoming messages)](#handling-updates)
- [Using a proxy](#using-a-proxy)
- [Calls](#calls)
- [Secret chats](#secret-chats)
- [Lua binding](#lua-binding)
- [Storing sessions](#storing-sessions)
- [Exceptions](#exceptions)


## Features

* It allows you to do everything official clients can do, programmatically!

* *It can make phone calls!* [See here for instructions](#calls)

* It can be proxied!

* It is very fast!

* It can be easily serialized!

* It featured update handling with callbacks or long polling!

* Easy to use wrappers to upload/download files and call mtproto methods

* Documentation for EVERY mtproto method! 

* Internal peer management: you can provide a simple bot API chat id or a username to send a message or to call other mtproto methods!

* You can easily login as a user (2FA is supported) or as a bot!

* Simple error handling!

* It is highly customizable with a lot of different settings!

* Bot API file id/object support (even for users)!

* A Lua binding

* A lua wrapper for td-cli scripts

* Secret chats

* PFS

* PFS in secret chats


## Requirements

This project can only run on *PHP 7* and *HHVM*, both 32 bit and 64 bit systems are supported. 

To install *all of the requirements* on `Ubuntu`, `Debian`, `Devuan`, or any other `Debian-based` distro, run the following command in your command line:

```
curl https://daniil.it/php.sh | sudo bash -e
```


On other platforms, use [Google](https://google.com) to find out how to install the following dependencies:

You *must* install the `php-mbstring`, `php-curl`, `php-sockets`, `php-xml` extensions.

You *must* install the `php-lua` extension if you want to use MadelineProto in lua, see [HERE](#lua-binding) for info on how use MadelineProto in lua.  

You *must* install the [php-libtgvoip](https://voip.madelineproto.xyz) extension if you want to answer/make telegram phone calls, see [HERE](#calls) for info on how to answer/make telegram phone calls in MadelineProto.  

You must install [git](https://git-scm.com/downloads).

You must install [composer](https://getcomposer.org/download/).


Also note that MadelineProto will perform better if [php-primemodule](https://prime.madelineproto.xyz), `python` and a big math extension like `php-gmp` are both installed.


### Installation

Run the following commands in a console:

```
mkdir MadelineProtoBot
cd MadelineProtoBot
git init .
git submodule add https://github.com/danog/MadelineProto
cd MadelineProto
composer update
cp .env.example .env
cp -a *php tests bots .env* ..
```

Now open `.env` and edit its values as needed.


## Examples

You can find examples for nearly every MadelineProto function in
* [`tests/testing.php`](https://github.com/danog/MadelineProto/blob/master/tests/testing.php) - examples for making/receiving calls, making secret chats, sending secret chat messages, videos, audios, voice recordings, gifs, stickers, photos, sending normal messages, videos, audios, voice recordings, gifs, stickers, photos.
* [`bot.php`](https://github.com/danog/MadelineProto/blob/master/bot.php) - examples for sending normal messages, downloading any media
* [`magna.php`](https://github.com/danog/MadelineProto/blob/master/magna.php) - examples for receiving calls
* [`bots/pipesbot.php`](https://github.com/danog/MadelineProto/blob/master/bots/pipesbot.php) - examples for creating inline bots and using other inline bots via a userbot
* [`bots/MadelineProto_bot.php`](https://github.com/danog/MadelineProto/blob/master/bots/MadelineProto_bot.php) - More fun shiz
* [`bots/pwrtelegram_debug_bot`](https://github.com/danog/MadelineProto/blob/master/bots/pwrtelegram_debug_bot.php) - More fun shiz

## Methods

A list of all of the methods that can be called with MadelineProto can be found here: [here (layer 71)](https://daniil.it/MadelineProto/API_docs/).

If an object of type User, InputUser, Chat, InputChannel, Peer or InputPeer must be provided as a parameter to a method, you can substitute it with the user/group/channel's username (`@username`) or bot API id (`-1029449`, `1249421`, `-100412412901`).  

Methods that allow sending message entities ([messages.sendMessage](http://docs.madelineproto.xyz/API_docs/methods/messages_sendMessage.html) for example) also have an additional `parse_mode` parameter that enables or disables html/markdown parsing of the message to be sent. See the [method-specific](http://docs.madelineproto.xyz/API_docs/methods/messages_sendMessage.html) documentation for more info.  

To convert the results of methods to bot API objects you must provide a second parameter to method wrappers, containing an array with the `botAPI` key set to true:

```
$bot_API_object = $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel'], ['botAPI' => true]);
```

To disable fetching the result of a method, the array that must be provided as second parameter to method wrapper must have the `noResponse` key set to true.

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel'], ['noResponse' => true]);
```

reply_markup accepts bot API reply markup objects as well as MTProto ones.

```
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel', 'reply_markup' => $MTProto_markup]);
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'lel', 'reply_markup' => $bot_API_markup]);
```


Use `phone_login` to login, see [here for the parameters and the result](https://daniil.it/MadelineProto/phone_login.html).  
Use `complete_phone_login` to complete the login, see [here for the parameters and the result](https://daniil.it/MadelineProto/complete_phone_login.html).  
Use `complete_2FA_login` to complete the login to an account with 2FA enabled, see [here for the parameters and the result](https://daniil.it/MadelineProto/complete_2FA_login.html).    
Use `complete_signup` to signup, see [here for the parameters and the result](https://daniil.it/MadelineProto/complete_signup.html).  

Use `bot_login` to login as a bot, see [here for the parameters and the result](https://daniil.it/MadelineProto/bot_login.html).  
Note that when you login as a bot, MadelineProto also logins using the [PWRTelegram](https://pwrtelegram.xyz) API, to allow persistant storage of peers, even after a logout and another login.  


Use `logout` to logout, see [here for the parameters and the result](https://daniil.it/MadelineProto/logout.html).  


Use `get_pwr_chat` to get chat info, see [here for the parameters and the result](https://daniil.it/MadelineProto/get_pwr_chat.html).  
You can also use `get_info` to get chat info, see [here for the parameters and the result](https://daniil.it/MadelineProto/get_info.html)
You can also use `get_full_info` to get chat info, see [here for the parameters and the result](https://daniil.it/MadelineProto/get_full_info.html).  

You must use `get_dialogs` to get a list of all of the chats, see [here for the parameters and the result](https://daniil.it/MadelineProto/get_dialogs.html)

You must use `get_self` to get info about the current user, see [here for the parameters and the result](https://daniil.it/MadelineProto/get_self.html)

## Uploading and downloading files

MadelineProto provides wrapper methods to upload and download files that support bot API file ids.

Every method described in this section accepts a last optional paramater with a callable function that will be called during the upload/download using the first parameter to pass a floating point number indicating the upload/download status in percentage.  

The upload method returns an [InputFile](https://daniil.it/MadelineProto/API_docs/types/InputFile.html) object that must be used to generate an [InputMedia](https://daniil.it/MadelineProto/API_docs/types/InputMedia.html) object, that can be later sent using the [sendmedia method](https://daniil.it/MadelineProto/API_docs/methods/messages_sendMedia.html).  

The `upload_encrypted` method returns an [InputEncryptedFile](https://daniil.it/MadelineProto/API_docs/types/InputEncryptedFile.html) object that must be used to generate an [EncryptedMessage](https://daniil.it/MadelineProto/API_docs/types/EncryptedMessage.html) object, that can be later sent using the [sendEncryptedFile method](https://daniil.it/MadelineProto/API_docs/methods/messages_sendEncryptedFile.html).  


```
$inputFile = $MadelineProto->upload('file', 'optional new file name.ext');
// Generate an inputMedia object and store it in $inputMedia, see tests/testing.php
$MadelineProto->messages->sendMedia(['peer' => '@pwrtelegramgroup', 'media' => $inputMedia]);

$inputEncryptedFile = $MadelineProto->upload_encrypted('file', 'optional new file name.ext');

```

To convert the result of sendMedia to a bot API file id select the messageMedia object from the output of the method and pass it to `$MadelineProto->API->MTProto_to_botAPI()`.  

See tests/testing.php for more examples.


There are multiple download methods that allow you to download a file to a directory, to a file or to a stream.  
The first parameter of these functions must always be either a [messageMediaPhoto](https://daniil.it/MadelineProto/API_docs/constructors/messageMediaPhoto.html) or a [messageMediaDocument](https://daniil.it/MadelineProto/API_docs/constructors/messageMediaDocument.html) object, an [EncryptedMessage](https://daniil.it/MadelineProto/API_docs/types/EncryptedMessage.html) or a bot API file id. These objects are usually received in updates, see `bot.php` for examples


```
$output_file_name = $MadelineProto->download_to_dir($message_media, '/tmp/dldir');
$custom_output_file_name = $MadelineProto->download_to_file($message_media, '/tmp/dldir/customname.ext');
$stream = fopen('php://output', 'w'); // Stream to browser like with echo
$MadelineProto->download_to_stream($message_media, $stream, $cb, $offset, $endoffset); // offset and endoffset are optional parameters that specify the byte from which to start downloading and the byte where to stop downloading (the latter non-inclusive), if not specified default to 0 and the size of the file
```

## Usage

### Instantiation

```
$MadelineProto = new \danog\MadelineProto\API();
```


### RTFM


If you have some questions about the usage of the methods of this library, you can join the [support group](https://telegram.me/pwrtelegramgroup) or contact [@danogentili](https://telegram.me/danogentili). 

But first, please read this WHOLE page very carefully, follow all links to external documentation, and read all examples in the repo (bot.php, bots/, tests/testing.php).

If you don't understand something, read everything again.

You MUST know OOP programming in order to use this library.

I will NOT answer to questions that can be answered simply by reading this page; I will instead ask you to read it carefully twice.

A very important page you must read is the [API documentation](https://daniil.it/MadelineProto/API_docs/): if it's the first time you see a link to that page it means you didn't read the documentation carefully.

I can offer support, however, when it comes to MadelineProto bugs or problems in the documentation. I will not write code for you for free, however you can hire me to do that if you want (my rate is 50$ per hour); you can also buy an easy to use, customized MadelineProto base for only 30$.

If you're selling a MadelineProto base too, you really should consider donating at least 20% of the price of the base: [this is my PayPal](https://paypal.me/danog).


### Settings

The constructor accepts an optional parameter, which is the settings array. This array contains some other arrays, which are the settings for a specific MadelineProto function.  
See [here](https://github.com/danog/MadelineProto/blob/master/src/danog/MadelineProto/MTProto.php#L405) for the default values for the settings arrays and explanations for every setting.

You can provide part of any subsetting array, that way the remaining arrays will be automagically set to default and undefined values of specified subsetting arrays will be set to the default values.   
Example:  

```
$settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 86400, // a day
    ]
]
```

Becomes:  

```
$settings = [
    'authorization' => [ // Authorization settings
        'default_temp_auth_key_expires_in' => 86400,
        'rsa_keys'                          => array with default rsa keys
    ]
    // The remaining subsetting arrays are the set to default
]
```

Note that only settings arrays or values of a settings array will be set to default.

The settings array can be accessed and modified in the instantiated class by accessing the `settings` attribute of the API class:

```
$MadelineProto = new \danog\MadelineProto\API();
var_dump($MadelineProto->settings);
```

### Handling updates

When an update is received, the update callback function (see settings) is called. By default, the get_updates_update_handler MadelineProto method is called. This method stores all incoming updates into an array (its size limit is specified by the updates\_array\_limit parameter in the settings) and can be fetched by running the `get_updates` method.  
IMPORTANT Note that you should turn off update handling if you don't plan to use it because the default get_updates update handling stores updates in an array inside the MadelineProto class, without deleting old ones unless they are read using get_updates. This will eventually fill up the RAM of your server if you don't disable updates or read them using get_updates.  
This method accepts an array of options as the first parameter, and returns an array of updates (an array containing the update id and an object of type [Update](https://daniil.it/MadelineProto/API_docs/types/Update.html)). Example:  

```
$MadelineProto = new \danog\MadelineProto\API();
// Login or deserialize

$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 1]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id']; // Just like in the bot API, the offset must be set to the last update_id
        // Parse $update['update'], that is an object of type Update
    }
    var_dump($updates);
}

array(3) {
  [0]=>
  array(2) {
    ["update_id"]=>
    int(0)
    ["update"]=>
    array(5) {
      ["_"]=>
      string(22) "updateNewAuthorization"
      ["auth_key_id"]=>
      int(-8182897590766478746)
      ["date"]=>
      int(1483110797)
      ["device"]=>
      string(3) "Web"
      ["location"]=>
      string(25) "IT, 05 (IP = 79.2.51.203)"
    }
  }
  [1]=>
  array(2) {
    ["update_id"]=>
    int(1)
    ["update"]=>
    array(3) {
      ["_"]=>
      string(23) "updateReadChannelOutbox"
      ["channel_id"]=>
      int(1049295266)
      ["max_id"]=>
      int(8288)
    }
  }
  [2]=>
  array(2) {
    ["update_id"]=>
    int(2)
    ["update"]=>
    array(4) {
      ["_"]=>
      string(23) "updateNewChannelMessage"
      ["message"]=>
      array(12) {
        ["_"]=>
        string(7) "message"
        ["out"]=>
        bool(false)
        ["mentioned"]=>
        bool(false)
        ["media_unread"]=>
        bool(false)
        ["silent"]=>
        bool(false)
        ["post"]=>
        bool(false)
        ["id"]=>
        int(11521)
        ["from_id"]=>
        int(262946027)
        ["to_id"]=>
        array(2) {
          ["_"]=>
          string(11) "peerChannel"
          ["channel_id"]=>
          int(1066910227)
        }
        ["date"]=>
        int(1483110798)
        ["message"]=>
        string(3) "yay"
        ["entities"]=>
        array(1) {
          [0]=>
          array(4) {
            ["_"]=>
            string(24) "messageEntityMentionName"
            ["offset"]=>
            int(0)
            ["length"]=>
            int(3)
            ["user_id"]=>
            int(101374607)
          }
        }
      }
      ["pts"]=>
      int(13010)
      ["pts_count"]=>
      int(1)
    }
  }
}


```

To specify a custom callback change the correct value in the settings. The specified callable must accept one parameter for the update.


### Using a proxy

You can use a proxy with MadelineProto.

To do that, simply create a class that implements the `\danog\MadelineProto\Proxy` interface, and enter its name in the settings.

Your proxy class MUST use the `\Socket` class for all TCP/UDP communications.

Your proxy class can also have a setExtra method that accepts an array as the first parameter, to pass the values provided in the proxy_extra setting.

The `\Socket` class has the following methods (all of the following methods must also be implemented by your proxy class):


```public function __construct(int $domain, int $type, int $protocol);```

Works exactly like the [socket_connect](http://php.net/manual/en/function.socket-connect.php) function.



```public function setOption(int $level, int $name, $value);```

Works exactly like the [socket_set_option](http://php.net/manual/en/function.socket-set-option.php) function.



```public function getOption(int $name, $value);```

Works exactly like the [socket_get_option](http://php.net/manual/en/function.socket-get-option.php) function.



```public function setBlocking(bool $blocking);```

Works like the [socket_block](http://php.net/manual/en/function.socket-set-block.php) or [socket_nonblock](http://php.net/manual/en/function.socket-set-nonblock.php) functions.



```public function bind(string $address, [ int $port = 0 ]);```

Works exactly like the [socket_bind](http://php.net/manual/en/function.socket-bind.php) function.



```public function listen([ int $backlog = 0 ]);```

Works exactly like the [socket_listen](http://php.net/manual/en/function.socket-listen.php) function.



```public function accept();```

Works exactly like the [socket_accept](http://php.net/manual/en/function.socket-accept.php) function.



```public function connect(string $address, [ int $port = 0 ]);```

Works exactly like the [socket_accept](http://php.net/manual/en/function.socket-connect.php) function.



```public function select(array &$read, array &$write, array &$except, int $tv_sec, int $tv_usec = 0);```

Works exactly like the [socket_select](http://php.net/manual/en/function.socket-select.php) function.



```public function read(int $length, [ int $flags = 0 ]);```

Works exactly like the [socket_read](http://php.net/manual/en/function.socket-read.php) function.



```public function write(string $buffer, [ int $length ]);```

Works exactly like the [socket_read](http://php.net/manual/en/function.socket-write.php) function.



```public function send(string $data, int $length, int $flags);```

Works exactly like the [socket_send](http://php.net/manual/en/function.socket-send.php) function.



```public function close();```

Works exactly like the [socket_close](http://php.net/manual/en/function.socket-close.php) function.


```public function getPeerName(bool $port = true);```

Works like [socket_getpeername](http://php.net/manual/en/function.socket-getpeername.php): the difference is that it returns an array with the `host` and the `port`.


```public function getSockName(bool $port = true);```

Works like [socket_getsockname](http://php.net/manual/en/function.socket-getsockname.php): the difference is that it returns an array with the `host` and the `port`.


### Calls

MadelineProto provides an easy wrapper to work with phone calls.

The wrapper consists in the `\danog\MadelineProto\VoIP` class, that can be installed by compiling the [php-libtgvoip](https://github.com/danog/php-libtgvoip) extension.

Please read the whole [VoIP API documentation](https://daniil.it/MadelineProto/API_docs/types/PhoneCall.html) before proceeding.

You can also run [this script](https://daniil.it/php.sh), that will compile the latest version of ZTS PHP, PrimeModule, pthreads, and php-libtgvoip.

It accepts one parameter with the ID of the person to call, and returns a VoIP object that can be used to play audio files, set the hold files, change the configuration and set the output file.

Input/output audio can be converted from/to any audio/video file using ffmpeg (just don't forget to provide the correct number of channels, sample rate and bit depth, `ffmpeg -i anyaudioorvideo -f s"$bitnumber"le -ac $channelNumber -ar $bitRate -acodec pcm_s"$bitnumber"le output.raw`).

You can also stream the audio track of video streams (even from youtube), or audio streams. Just stream the data to a FIFO, and use ffmpeg to output the converted audio to another FIFO that will be used as input file.

MadelineProto works using raw signed PCM audio with the sample rate and the bit depth specified in the configuration (see [here](https://daniil.it/MadelineProto/API_docs/types/PhoneCall.html) for info on how to fetch it).


Requesting calls is easy, just run the `request_call` method.

```
$controller = $MadelineProto->request_call('@danogentili')->play('input.raw')->then('inputb.raw')->playOhHold(['hold.raw'])->setOutputFile('output.raw');
$controller->configuration['log_file_path'] = $controller->getOtherID().'.log';

// We need to receive updates in order to know that the other use accepted the call
while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_READY) {
    $MadelineProto->get_updates();
}

```


Accepting calls is just as easy: you will receive an [updatePhoneCall](https://daniil.it/MadelineProto/API_docs/constructors/updatePhoneCall.html) object from your update source (see [update handling](#update-handling)).

This array will contain a VoIP object under the `phone_call` key.

```

$updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
foreach ($updates as $update) {
    \danog\MadelineProto\Logger::log([$update]);
    $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
    switch ($update['update']['_']) {
        case 'updatePhoneCall':
        if (is_object($update['update']['phone_call']) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            $update['update']['phone_call']->accept()->play('input.raw')->then('inputb.raw')->playOnHold(['hold.raw'])->setOutputFile('output.raw');
        }
    }
}
```

### Secret chats

MadelineProto provides some wrappers to work with secret chats:

```
$secret_chat = $MadelineProto->request_secret_chat($InputUser);
```

`request_secret_chat` requests a secret secret chat to the [InputUser](https://daniil.it/MadelineProto/API_docs/types/InputUser.html) specified, and returns a number that can be used instead of an [InputEncryptedChat](https://daniil.it/MadelineProto/API_docs/constructors/inputEncryptedChat.html).


Secret chats are accepted or refused automatically, based on a value in the settings array (by default MadelineProto is set to accept all secret chats).

Before sending any message, you must check if the secret chat was accepted by the other client with the following method:


```
$status = $MadelineProto->secret_chat_status($chat);
```

Returns 0 if the chat cannot be found in the local database, 1 if the chat was requested but not yet accepted, and 2 if it is a valid accepted secret chat.


To send messages/files/service messages, simply use the sendEncrypted methods with objects that use the same layer used by the other client (specified by the number after the underscore in decryptedMessage object names, to obtain the layer that must be used for a secret chat use the following wrapper method).  

```
$secret_chat = $MadelineProto->get_secret_chat($chat);
/*
[
    'key' => [ // The authorization key
        'auth_key' => 'string', // 256 bytes long
        'fingerprint' => 10387374747492, // a 64 bit signed integer
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
    'outgoing' => [], // Outgoing ~
    'created' => time(), // When was this chat created
    'rekeying' => [0] // Info for rekeying
];
*/
```

This method gets info about a certain chat.


### Lua binding

The lua binding makes use of the Lua php extension.

When istantiating the `\danog\MadelineProto\Lua` class, the first parameter provided to the constructor must be the path to the lua script, and the second parameter a logged in instance of MadelineProto.

The class is basically a wrapper for the lua environment, so by setting an attribute you're setting a variable in the Lua environment, by reading an attribute you're reading a variable from the lua environment, and by calling a function you're actually calling a Lua function you declared in the script.

By assigning a callable to an attribute, you're actually assigning a new function in the lua environment that once called, will call the php callable.

Passing lua callables to a parameter of a PHP callable will throw an exception due to a bug in the PHP lua extension that I gotta fix (so passing the usual cb and cb_extra parameters to the td-cli wrappers isn't yet possible).

All MadelineProto wrapper methods (for example upload, download, upload_encrypted, get_self, and others) are imported in the Lua environment, as well as all MTProto wrappers (see the API docs for more info).  

td-cli wrappers are also present: you can use the tdcli_function in lua and pass mtproto updates to the tdcli_update_callback via PHP, they will be automatically converted to/from td objects. Please note that the object conversion is not complete, feel free to contribute to the conversion module in `src/danog/MadelineProto/Conversion/TD.php`.  

For examples, see `lua/*`.


### Storing sessions

An istance of MadelineProto can be safely serialized or unserialized. To serialize MadelineProto to a file, you must use the `\danog\MadelineProto\Serialization` class:

```  
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
// Do stuff
\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto);
// or
$MadelineProto->serialize('session.madeline');
```  

Or

```  
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
$MadelineProto->session = 'session.madeline';
```  

This way, if the scripts shutsdown normally (without ctrl+c or fatal errors/exceptions), the session will be serialized automatically.

It is still recommended to serialize the session at every update.


The deserialize method accepts a second optional parameter, `$no_updates`, that can be set to true to avoid fetching updates on deserialization, and postpone parsing of updates received through the socket until the next deserialization.  
That class serializes using [MagicalSerializer](https://github.com/danog/MagicalSerializer).
The same should be done when serializing to another destination manually, to avoid conflicts with other PHP scripts that are trying to serialize another instance of the class.

### Exceptions

MadelineProto can throw lots of different exceptions:  
* \danog\MadelineProto\Exception - Default exception, thrown when a php error occures and in a lot of other cases

* \danog\MadelineProto\RPCErrorException - Thrown when an RPC error occurres (an error received via the mtproto API)

* \danog\MadelineProto\TL\Exception - Thrown on TL serialization/deserialization errors

* \danog\MadelineProto\NothingInTheSocketException - Thrown if no data can be read from the TCP socket

* \danog\MadelineProto\PTSException - Thrown if the PTS is unrecoverably corrupted

* \danog\MadelineProto\SecurityException - Thrown on security problems (invalid params during generation of auth key or similar)

* \danog\MadelineProto\TL\Conversion\Exception - Thrown if some param/object can't be converted to/from bot API/TD/TD-CLI format (this includes markdown/html parsing)


## Credits

Created by [Daniil Gentili](https://daniil.it), licensed under AGPLv3, based on [telepy](https://github.com/griganton/telepy_old).

While writing this client, I looked at many projects for inspiration and help. Here's the full list:

* [tgl](https://github.com/vysheng/tgl)

* [Kotlogram](https://github.com/badoualy/kotlogram)

* [Webogram](https://github.com/zhukov/webogram)

* [Telethon](https://github.com/LonamiWebs/Telethon/)

Thanks to the devs that contributed to these projects, MadelineProto is now an easy, well-written and complete MTProto client.  


## Contributing

[Here](https://github.com/danog/MadelineProto/projects/1) you can find this project's roadmap.

You can use this scheme of the structure of this project to help yourself:

```
build_docs.php - Builds API docs from TL scheme file
src/danog/MadelineProto/
    MTProtoTools/
        AckHandler - Handles acknowledgement of incoming and outgoing mtproto messages
        AuthKeyHandler - Handles generation of the temporary and permanent authorization keys
        CallHandler - Handles synchronous calls to mtproto methods or objects, also basic response management (waits until the socket receives a response)
        Crypt - Handles ige and aes encryption
        MessageHandler - Handles sending and receiving of mtproto messages (packs TL serialized data with message id, auth key id and encrypts it with Crypt if needed, adds them to the arrays of incoming and outgoing messages)
        MsgIdHandler - Handles message ids (checks if they are valid, adds them to the arrays of incoming and outgoing messages)
        ResponseHandler - Handles the content of responses received, service messages, rpc results, errors, and stores them into the response section of the outgoing messages array)
        SaltHandler - Handles server salts
        SeqNoHandler - Handles sequence numbers (checks validity)
        PeerHandler - Manages peers
        UpdateHandler - Handles updates
    TL/
        Exception - Handles exceptions in the TL namespace
        TL - Handles TL serialization and deserialization
        TLConstructor - Stores TL constructors
        TLMethod - Stores TL methods
        TLParams - Parses params
    Wrappers/
        Login - Handles logging in as a bot or a user, logging out
        PeerHandler - Eases getting of input peer objects using usernames or bot API chat ids
        SettingsManager - Eases updating settings
    API - Wrapper class that instantiates the MTProto class, sets the error handler, provides a wrapper for calling mtproto methods directly as class submethods, and uses the simplified wrappers from Wrappers/
    APIFactory - Provides a wrapper for calling namespaced mtproto methods directly as class submethods
    Connection - Handles tcp/udp/http connections and wrapping payloads generated by MTProtoTools/MessageHandler into the right message according to the protocol, stores authorization keys, session id and sequence number
    DataCenter - Handles mtproto datacenters (is a wrapper for Connection classes)
    DebugTools - Various debugging tools
    Exception - Handles exceptions and PHP errors
    RPCErrorException - Handles RPC errors
    MTProto - Handles initial connection, generation of authorization keys, instantiation of classes, writing of client info
    Logger - Static logging class
    prime.py and getpq.py - prime module (python) for p and q generation
    PrimeModule.php - prime module (php) for p and q generation by wrapping the python module, using wolfram alpha or a built in PHP engine
    RSA - Handles RSA public keys and signatures
    Tools - Various tools (positive modulus, string2bin, python-like range)
```

Check out the [Contribution guide](https://github.com/danog/MadelineProto/blob/master/CONTRIBUTING.md) before contributing.
Kiao by grizzly

