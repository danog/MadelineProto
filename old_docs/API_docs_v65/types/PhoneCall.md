---
title: PhoneCall
description: constructors and methods of type PhoneCall
---
## Type: PhoneCall  
[Back to types index](index.md)



This is an object of type `\danog\MadelineProto\VoIP`.

It will only be available if the [php-libtgvoip](https://github.com/danog/php-libtgvoip) extension is installed, see [the main docs](https://daniil.it/MadelineProto#calls) for an easy installation script.

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
* `getProtocol()` - Gets the protocol used by the current call, as a [PhoneCallProtocol](https://daniil.it/MadelineProto/API_docs/types/PhoneCallProtocol.html) object
* `getCallID()` - Gets the call ID, as an [InputPhoneCall](https://daniil.it/MadelineProto/API_docs/types/InputPhoneCall.html) object
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

`$reason` - can be a [PhoneCallDiscardReason](https://daniil.it/MadelineProto/API_docs/types/PhoneCallDiscardReason.html) object (defaults to a [phoneCallDiscardReasonDisconnect](https://daniil.it/MadelineProto/API_docs/constructors/phoneCallDiscardReasonDisconnect.html) object).

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
var_dump($call->storage["pony"]); // fluttershy
```

Note: when modifying this property, *never* overwrite the previous values. Always either modify the values of the array separately like showed above, or use array_merge.


* `configuration`: An array containing the libtgvoip configuration.

You can only modify the data saving mode, the network type, the logging file path and the stats dump file path:  

Example:

```
$call->configuration["log_file_path"] = "logs".$call->getOtherID().".log"; // Default is /dev/null
$call->configuration["stats_dump_file_path"] = "stats".$call->getOtherID().".log"; // Default is /dev/null
$call->configuration["network_type"] = \danog\MadelineProto\VoIP::NET_TYPE_WIFI; // Default is NET_TYPE_ETHERNET
$call->configuration["data_saving"] = \danog\MadelineProto\VoIP::DATA_SAVING_MOBILE; // Default is DATA_SAVING_NEVER
$call->parseConfig(); // Always call this after changing settings
```

Note: when modifying this property, *never* overwrite the previous values. Always either modify the values of the array separately like showed above, or use array_merge.

After modifying it, you must always parse the new configuration with a call to `parseConfig`.

