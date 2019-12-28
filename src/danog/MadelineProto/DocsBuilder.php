<?php

/**
 * DocsBuilder module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\TL\TL;

// This code was written a few years ago: it is garbage, and has to be rewritten
class DocsBuilder
{
    use \danog\MadelineProto\DocsBuilder\Methods;
    use \danog\MadelineProto\DocsBuilder\Constructors;
    use Tools;
    public $td = false;

    public function __construct($logger, $settings)
    {
        $this->logger = $logger;
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $this->TL = new TL(new class($logger) {
            public function __construct($logger)
            {
                $this->logger = $logger;
            }
        });
        $this->TL->init($settings['tl_schema']);
        if (isset($settings['tl_schema']['td']) && !isset($settings['tl_schema']['telegram'])) {
            $this->td = true;
        }
        $this->settings = $settings;
        if (!\file_exists($this->settings['output_dir'])) {
            \mkdir($this->settings['output_dir']);
        }
        \chdir($this->settings['output_dir']);
        $this->index = $settings['readme'] ? 'README.md' : 'index.md';
    }

    public $types = [];
    public $any = '*';

    public function mkDocs()
    {
        \danog\MadelineProto\Logger::log('Generating documentation index...', \danog\MadelineProto\Logger::NOTICE);
        \file_put_contents($this->index, '---
title: '.$this->settings['title'].'
description: '.$this->settings['description'].'
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# '.$this->settings['description'].'  

[Back to main documentation](..)  


[Methods](methods/)

[Constructors](constructors/)

[Types](types/)');
        $this->mkmethodS();
        $this->mkConstructors();
        foreach (\glob('types/*') as $unlink) {
            \unlink($unlink);
        }
        if (\file_exists('types')) {
            \rmdir('types');
        }
        \mkdir('types');
        \ksort($this->types);
        $index = '';
        \danog\MadelineProto\Logger::log('Generating types documentation...', \danog\MadelineProto\Logger::NOTICE);
        $last_namespace = '';
        foreach ($this->types as $otype => $keys) {
            $new_namespace = \preg_replace('/_.*/', '', $otype);
            //$br = $new_namespace != $last_namespace ? '***<br><br>' : '';
            $type = \str_replace(['<', '>'], ['_of_', ''], $otype);
            $type = \preg_replace('/.*_of_/', '', $type);
            $index .= '['.\str_replace('_', '\\_', $type).']('.$type.'.md)<a name="'.$type.'"></a>  

';
            $constructors = '';
            foreach ($keys['constructors'] as $data) {
                $predicate = $data['predicate'].(isset($data['layer']) && $data['layer'] !== '' ? '_'.$data['layer'] : '');
                $md_predicate = \str_replace('_', '\\_', $predicate);
                $constructors .= '['.$md_predicate.'](../constructors/'.$predicate.'.md)  

';
            }
            $methods = '';
            foreach ($keys['methods'] as $data) {
                $name = $data['method'];
                $md_name = \str_replace(['.', '_'], ['->', '\\_'], $name);
                $methods .= '[$MadelineProto->'.$md_name.'](../methods/'.$name.'.md)  

';
            }
            $description = isset($this->td_descriptions['types'][$otype]) ? $this->td_descriptions['types'][$otype] : 'constructors and methods of type '.$type;
            $symFile = \str_replace('.', '_', $type);
            $redir = $symFile !== $type ? "\nredirect_from: /API_docs/types/$symFile.html" : '';

            $header = '---
title: '.$type.'
description: constructors and methods of type '.$type.'
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png'.$redir.'
---
# Type: '.\str_replace('_', '\\_', $type).'  
[Back to types index](index.md)



';
            $header .= isset($this->td_descriptions['types'][$otype]) ? $this->td_descriptions['types'][$otype].PHP_EOL.PHP_EOL : '';
            if (!isset($this->settings['td'])) {
                if (\in_array($type, ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputDialogPeer', 'DialogPeer', 'InputPeer', 'NotifyPeer', 'InputNotifyPeer'])) {
                    $header .= 'You can directly provide the [Update](Update.md) or [Message](Message.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntaxes can also be used:

```
$'.$type." = '@username'; // Username

\$".$type." = 'me'; // The currently logged-in user

\$".$type.' = 44700; // bot API id (users)
$'.$type.' = -492772765; // bot API id (chats)
$'.$type.' = -10038575794; // bot API id (channels)

$'.$type." = 'https://t.me/danogentili'; // t.me URLs
\$".$type." = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

\$".$type." = 'user#44700'; // tg-cli style id (users)
\$".$type." = 'chat#492772765'; // tg-cli style id (chats)
\$".$type." = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputDialogPeer](InputDialogPeer.md), an [InputNotifyPeer](InputNotifyPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), an [DialogPeer](DialogPeer.md), [NotifyPeer](NotifyPeer.md), or a [Chat](Chat.md) object can also be used.\n\n\n";
                }
                if (\in_array($type, ['InputEncryptedChat'])) {
                    $header .= 'You can directly provide the [Update](Update.md) or [EncryptedMessage](EncryptedMessage.md) object here, MadelineProto will automatically extract the destination chat id.

The following syntax can also be used:

```
$'.$type.' = -147286699; // Numeric chat id returned by requestSecretChat, can be positive or negative
```


';
                }
                if (\in_array($type, ['InputFile', 'InputEncryptedFile'])) {
                    $header .= 'The following syntax can also be used:

```
$'.$type.' = \'filename.mp4\'; // The file path can also be used
```


';
                }
                if (\in_array($type, ['InputPhoto'])) {
                    $header .= 'You can also provide a [MessageMedia](MessageMedia.md), [Message](Message.md), [Update](Update.md), [Photo](Photo.md) here, MadelineProto will automatically convert it to the right type.

';
                }
                if (\in_array($type, ['InputDocument'])) {
                    $header .= 'You can also provide a [MessageMedia](MessageMedia.md), [Message](Message.md), [Update](Update.md), [Document](Document.md) here, MadelineProto will automatically convert it to the right type.

';
                }
                if (\in_array($type, ['InputMedia'])) {
                    $header .= 'You can also provide a [MessageMedia](MessageMedia.md), [Message](Message.md), [Update](Update.md), [Document](Document.md), [Photo](Photo.md), [InputDocument](InputDocument.md), [InputPhoto](InputPhoto.md) here, MadelineProto will automatically convert it to the right type.

';
                }
                if (\in_array($type, ['InputMessage'])) {
                    $header .= 'The following syntax can also be used:

```
$'.$type.' = 142; // Numeric message ID
```


';
                }
                if (\in_array($type, ['KeyboardButton'])) {
                    $header .= 'Clicking these buttons:

To click these buttons simply run the `click` method:  

```
$result = $'.$type.'->click();
```

`$result` can be one of the following:


* A string - If the button is a keyboardButtonUrl

* [Updates](Updates.md) - If the button is a keyboardButton, the message will be sent to the chat, in reply to the message with the keyboard

* [messages.BotCallbackAnswer](messages.BotCallbackAnswer.md) - If the button is a keyboardButtonCallback or a keyboardButtonGame the button will be pressed and the result will be returned

* `false` - If the button is an unsupported button, like keyboardButtonRequestPhone, keyboardButtonRequestGeoLocation, keyboardButtonSwitchInlinekeyboardButtonBuy; you will have to parse data from these buttons manually


You can also access the properties of the constructor as a normal array, for example $button[\'name\']
';
                }
            }
            $constructors = '### Possible values (constructors):

'.$constructors.'

';
            $methods = '### Methods that return an object of this type (methods):

'.$methods.'

';
            if (!isset($this->settings['td'])) {
                if (\in_array($type, ['PhoneCall'])) {
                    $methods = '';
                    $constructors = '';
                    $header .= 'This is an object of type `\\danog\\MadelineProto\\VoIP`.

It will only be available if the [php-libtgvoip](https://github.com/danog/php-libtgvoip) extension is installed, see [the main docs](https://docs.madelineproto.xyz#calls) for an easy installation script.

You MUST know [OOP](http://php.net/manual/en/language.oop5.php) to use this class.

## Constants:

VoIPController states (these constants are incrementing integers, thus can be compared like numbers):

* `STATE_CREATED` - controller created
* `STATE_WAIT_INIT` - controller inited
* `STATE_WAIT_INIT_ACK` - controller inited
* `STATE_ESTABLISHED` - connection established
* `STATE_FAILED` - connection failed
* `STATE_RECONNECTING` - reconnecting

VoIPController errors:

* `TGVOIP_ERROR_UNKNOWN` - An unknown error occurred
* `TGVOIP_ERROR_INCOMPATIBLE` - The other side is using an unsupported client/protocol
* `TGVOIP_ERROR_TIMEOUT` - A timeout occurred
* `TGVOIP_ERROR_AUDIO_IO` - An I/O error occurred

Network types (these constants are incrementing integers, thus can be compared like numbers):

* `NET_TYPE_UNKNOWN` - Unknown network type
* `NET_TYPE_GPRS` - GPRS connection
* `NET_TYPE_EDGE` - EDGE connection
* `NET_TYPE_3G` - 3G connection
* `NET_TYPE_HSPA` - HSPA connection
* `NET_TYPE_LTE` - LTE connection
* `NET_TYPE_WIFI` - WIFI connection
* `NET_TYPE_ETHERNET` - Ethernet connection (this guarantees high audio quality)
* `NET_TYPE_OTHER_HIGH_SPEED` - Other high speed connection
* `NET_TYPE_OTHER_LOW_SPEED` - Other low speed connection
* `NET_TYPE_DIALUP` - Dialup connection
* `NET_TYPE_OTHER_MOBILE` - Other mobile network connection

Data saving modes (these constants are incrementing integers, thus can be compared like numbers):

* `DATA_SAVING_NEVER` - Never save data (this guarantees high audio quality)
* `DATA_SAVING_MOBILE` - Use mobile data saving profiles
* `DATA_SAVING_ALWAYS` - Always use data saving profiles

Proxy settings (these constants are incrementing integers, thus can be compared like numbers):

* `PROXY_NONE` - No proxy
* `PROXY_SOCKS5` - Use the socks5 protocol

Audio states (these constants are incrementing integers, thus can be compared like numbers):

* `AUDIO_STATE_NONE` - The audio module was not created yet
* `AUDIO_STATE_CREATED` - The audio module was created
* `AUDIO_STATE_CONFIGURED` - The audio module was configured
* `AUDIO_STATE_RUNNING` - The audio module is running

Call states (these constants are incrementing integers, thus can be compared like numbers):

* `CALL_STATE_NONE` - The call was not created yet
* `CALL_STATE_REQUESTED` - This is an outgoing call
* `CALL_STATE_INCOMING` - This is an incoming call
* `CALL_STATE_ACCEPTED` - The incoming call was accepted, but not yet ready
* `CALL_STATE_CONFIRMED` - The outgoing call was accepted, but not yet ready
* `CALL_STATE_READY` - The call is ready. Audio data is being sent and received
* `CALL_STATE_ENDED` - The call is over.



## Methods:

* `getState()` - Gets the controller state, as a VoIPController state constant
* `getCallState()` - Gets the call state, as a call state constant
* `getVisualization()` - Gets the visualization of the encryption key, as an array of emojis, can be called only when the call state is bigger than or equal to `CALL_STATE_READY`. If called sooner, returns false.
* `getStats()` Gets connection stats
* `getOtherID()` - Gets the id of the other call participant, as a bot API ID
* `getProtocol()` - Gets the protocol used by the current call, as a [PhoneCallProtocol](https://docs.madelineproto.xyz/API_docs/types/PhoneCallProtocol.html) object
* `getCallID()` - Gets the call ID, as an [InputPhoneCall](https://docs.madelineproto.xyz/API_docs/types/InputPhoneCall.html) object
* `isCreator()` - Returns a boolean that indicates whether you are the creator of the call
* `whenCreated()` - Returns the unix timestamp of when the call was started (when was the call state set to `CALL_STATE_READY`)
* `getOutputState()` - Returns the state of the audio output module, as an audio state constant
* `getInputState()` - Returns the state of the audio input module, as an audio state constant
* `getDebugLog()` - Gets VoIPController debug log
* `getDebugString()` - Gets VoIPController debug string
* `getLastError()` - Gets the last error as a VoIPController error constant
* `getVersion()` - Gets VoIPController version
* `getSignalBarsCount()` - Gets number of signal bars (0-4)

* `parseConfig()` - Parses the configuration

* `accept()` - Accepts the phone call, returns `$this`
* `discard($reason = ["_" => "phoneCallDiscardReasonDisconnect"], $rating = [])` - Ends the phone call.

Accepts two optional parameters:

`$reason` - can be a [PhoneCallDiscardReason](https://docs.madelineproto.xyz/API_docs/types/PhoneCallDiscardReason.html) object (defaults to a [phoneCallDiscardReasonDisconnect](https://docs.madelineproto.xyz/API_docs/constructors/phoneCallDiscardReasonDisconnect.html) object).

`$rating` - Can be an array that must contain a rating, and a comment (`["rating" => 5, "comment" => "MadelineProto is very easy to use!"]). Defaults to an empty array.`



* `getOutputParams()` - Returns the output audio configuration

MadelineProto works using raw signed PCM audio, internally split in packets with `sampleNumber` samples.

The audio configuration is an array structured in the following way:
```
[
    "bitsPerSample" => int. // Bits in each PCM sample
    "sampleRate" => int, // PCM sample rate
    "channels" => int, // Number of PCM audio channels
    "sampleNumber" => int, // The audio data is internally split in packets, each having this number of samples
    "samplePeriod" => double, // PCM sample period in seconds, useful if you want to generate audio data manually
    "writePeriod" => double, // PCM write period in seconds (samplePeriod*sampleNumber), useful if you want to generate audio data manually
    "samplesSize" => int, // The audio data is internally split in packets, each having this number of bytes (sampleNumber*bitsPerSample/8)
    "level" => int // idk
];
```

* `getInputParams()` - Returns the input audio configuration

MadelineProto works using raw signed PCM audio, internally split in packets with `sampleNumber` samples.

The audio configuration is an array structured in the following way:
```
[
    "bitsPerSample" => int. // Bits in each PCM sample
    "sampleRate" => int, // PCM sample rate
    "channels" => int, // Number of PCM audio channels
    "sampleNumber" => int, // The audio data is internally split in packets, each having this number of samples
    "samplePeriod" => double, // PCM sample period in seconds, useful if you want to generate audio data manually
    "writePeriod" => double, // PCM write period in seconds (samplePeriod*sampleNumber), useful if you want to generate audio data manually
    "samplesSize" => int, // The audio data is internally split in packets, each having this number of bytes (sampleNumber*bitsPerSample/8)
];
```

* `play(string $file)` and `then(string $file)` - Play a certain audio file encoded in PCM, with the audio input configuration, returns `$this`
* `playOnHold(array $files)` - Array of audio files encoded in PCM, with the audio input configuration to loop on hold (when the files given with play/then have finished playing). If not called, no data will be played, returns `$this`
* `isPlaying()` - Returns true if MadelineProto is still playing the files given with play/then, false if the hold files (or nothing) is being played
* `setMicMute(bool $mute)` - Stops/resumes playing files/hold files, returns `$this`

* `setOutputFile(string $outputfile)` - Writes incoming audio data to file encoded in PCM, with the audio output configuration, returns `$this`
* `unsetOutputFile()` - Stops writing audio data to previously set file, returns `$this`


## Properties:

* `storage`: An array that can be used to store data related to this call.

Easy as pie:  

```
$call->storage["pony"] = "fluttershy";
\danog\MadelineProto\Logger::log($call->storage["pony"]); // fluttershy
```

Note: when modifying this property, *never* overwrite the previous values. Always either modify the values of the array separately like showed above, or use array_merge.


* `configuration`: An array containing the libtgvoip configuration.

You can only modify the data saving mode, the network type, the logging file path and the stats dump file path:  

Example:

```
$call->configuration["log_file_path"] = "logs".$call->getOtherID().".log"; // Default is /dev/null
$call->configuration["stats_dump_file_path"] = "stats".$call->getOtherID().".log"; // Default is /dev/null
$call->configuration["network_type"] = \\danog\\MadelineProto\\VoIP::NET_TYPE_WIFI; // Default is NET_TYPE_ETHERNET
$call->configuration["data_saving"] = \\danog\\MadelineProto\\VoIP::DATA_SAVING_MOBILE; // Default is DATA_SAVING_NEVER
$call->parseConfig(); // Always call this after changing settings
```

Note: when modifying this property, *never* overwrite the previous values. Always either modify the values of the array separately like showed above, or use array_merge.

After modifying it, you must always parse the new configuration with a call to `parseConfig`.

';
                }
            }
            if (\file_exists('types/'.$type.'.md')) {
                \danog\MadelineProto\Logger::log($type);
            }
            \file_put_contents('types/'.$type.'.md', $header.$constructors.$methods);
            $last_namespace = $new_namespace;
        }
        \danog\MadelineProto\Logger::log('Generating types index...', \danog\MadelineProto\Logger::NOTICE);
        \file_put_contents('types/'.$this->index, '---
title: Types
description: List of types
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# Types  
[Back to API documentation index](..)


'.$index);
        \danog\MadelineProto\Logger::log('Generating additional types...', \danog\MadelineProto\Logger::NOTICE);
        \file_put_contents('types/string.md', '---
title: string
description: A UTF8 string of variable length
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: string  
[Back to constructor index](index.md)

A UTF8 string of variable length. The total length in bytes of the string must not be bigger than 16777215.
');
        \file_put_contents('types/bytes.md', '---
title: bytes
description: A string of variable length
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: bytes  
[Back to constructor index](index.md)

An object of type `\danog\MadelineProto\TL\Types\Bytes`.  
When casted to string, turns into a string of bytes of variable length, with length smaller than or equal to 16777215.  
When JSON-serialized, turns into an array of the following format:  
```
[
   \'_\'     => \'bytes\',
   \'bytes\' => base64_encode($contents)
];
```
');
        \file_put_contents('types/int.md', '---
title: integer
description: A 32 bit signed integer ranging from -2147483648 to 2147483647
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: int  
[Back to constructor index](index.md)

A 32 bit signed integer ranging from `-2147483648` to `2147483647`.
');
        \file_put_contents('types/int53.md', '---
title: integer
description: A 53 bit signed integer
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: int53  
[Back to constructor index](index.md)

A 53 bit signed integer.
');
        \file_put_contents('types/long.md', '---
title: long
description: A 32 bit signed integer ranging from -9223372036854775808 to 9223372036854775807
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: long  
[Back to constructor index](index.md)

A 64 bit signed integer ranging from `-9223372036854775808` to `9223372036854775807`.
');
        \file_put_contents('types/int128.md', '---
title: int128
description: A 128 bit signed integer
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: int128  
[Back to constructor index](index.md)

A 128 bit signed integer represented in little-endian base256 (`string`) format.
');
        \file_put_contents('types/int256.md', '---
title: int256
description: A 256 bit signed integer
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: int256
[Back to constructor index](index.md)

A 256 bit signed integer represented in little-endian base256 (`string`) format.
');
        \file_put_contents('types/int512.md', '---
title: int512
description: A 512 bit signed integer
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: int512  
[Back to constructor index](index.md)

A 512 bit signed integer represented in little-endian base256 (`string`) format.
');
        \file_put_contents('types/double.md', '---
title: double
description: A double precision floating point number
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: double  
[Back to constructor index](index.md)

A double precision floating point number, single precision can also be used (float).
');
        \file_put_contents('types/!X.md', '---
title: !X
description: Represents a TL serialized payload
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: !X  
[Back to constructor index](index.md)

Represents a TL serialized payload.
');
        \file_put_contents('types/X.md', '---
title: X
description: Represents a TL serialized payload
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: X  
[Back to constructor index](index.md)

Represents a TL serialized payload.
');
        \file_put_contents('constructors/boolFalse.md', '---
title: boolFalse
description: Represents a boolean with value equal to false
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# boolFalse  
[Back to constructor index](index.md)

        Represents a boolean with value equal to `false`.
');
        \file_put_contents('constructors/boolTrue.md', '---
title: boolTrue
description: Represents a boolean with value equal to true
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# boolTrue  
[Back to constructor index](index.md)

Represents a boolean with value equal to `true`.
');
        \file_put_contents('constructors/null.md', '---
title: null
description: Represents a null value
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# null  
[Back to constructor index](index.md)

Represents a `null` value.
');
        \file_put_contents('types/Bool.md', '---
title: Bool
description: Represents a boolean.
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# Bool  
[Back to types index](index.md)

Represents a boolean.
');
        \file_put_contents('types/DataJSON.md', '---
title: DataJSON
description: Any json-encodable data
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
## Type: DataJSON
[Back to constructor index](index.md)

Any json-encodable data.
');
        \danog\MadelineProto\Logger::log('Done!', \danog\MadelineProto\Logger::NOTICE);
    }

    public static $template = '<?php
/**
 * Lang module
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */
    
namespace danog\MadelineProto;
    
class Lang
{
    public static $lang = %s;
    
    // THIS WILL BE OVERWRITTEN BY $lang["en"]
    public static $current_lang = %s;
}';

    public static function addToLang(string $key, string $value = '', bool $force = false)
    {
        if (!isset(\danog\MadelineProto\Lang::$lang['en'][$key]) || $force) {
            \danog\MadelineProto\Lang::$lang['en'][$key] = $value;
            \file_put_contents(__DIR__.'/Lang.php', \sprintf(self::$template, \var_export(\danog\MadelineProto\Lang::$lang, true), \var_export(\danog\MadelineProto\Lang::$lang['en'], true)));
        }
    }
}
