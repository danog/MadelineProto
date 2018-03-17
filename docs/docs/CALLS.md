# Calls

```
if (!file_exists('input.raw')) {
    echo 'Downloading example song'.PHP_EOL;
    copy('https://github.com/danog/MadelineProto/raw/master/input.raw', 'input.raw');
}
$call = $MadelineProto->request_call('@danogentili')->play('input.raw')->then('input.raw')->playOnHold(['input.raw'])->setOutputFile('output.raw');
```

MadelineProto provides an easy wrapper to work with phone calls.

The wrapper consists in the `\danog\MadelineProto\VoIP` class, that can be installed by compiling the [php-libtgvoip](https://voip.madelineproto.xyz) extension.

Please read the whole [VoIP API documentation](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) before proceeding.

## Requesting a call
```
$call = $MadelineProto->request_call('@danogentili');
```

The [request_call](https://docs.madelineproto.xyz/request_call.html) function accepts one parameter with the ID/username/Peer/User/InputPeer of the person to call, and returns a VoIP object that can be used to play audio files, set the hold files, change the configuration and set the output file (see the [VoIP API documentation](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) for more info).

MadelineProto works using raw signed PCM audio with the sample rate and the bit depth specified in the configuration (see [here](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) for info on how to fetch it): usually it's 1 channel, sample rate of 48khz, codec PCM s16 little endian.


## Playing mp3 files

Input/output audio can be converted from/to any audio/video file using ffmpeg:

```
ffmpeg -i anyaudioorvideo.mp3 -f s16le -ac 1 -ar 48000 -acodec pcm_s16le mysong.raw
```

## Playing streams

You can also play streams:

```
mkfifo mystream.raw
ffmpeg -i http://icestreaming.rai.it/1.mp3 -f s16le -ac 1 -ar 48000 -acodec pcm_s16le pipe:1 > mystream.raw
```

Remember: you can only play one fifo at a time. If you want to play the same stream in multiple calls, you must duplicate the data written to the fifo, by writing it to another fifo.


## Putting it all together

Requesting calls is easy, just run the `request_call` method.

```
$controller = $MadelineProto->request_call('@danogentili')->play('input.raw')->then('inputb.raw')->playOhHold(['hold.raw'])->setOutputFile('output.raw');
$controller->configuration['log_file_path'] = $controller->getOtherID().'.log';

// We need to receive updates in order to know that the other use accepted the call
while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_READY) {
    $MadelineProto->get_updates();
}

```


Accepting calls is just as easy: you will receive an [updatePhoneCall](https://docs.madelineproto.xyz/API_docs/constructors/updatePhoneCall.html) object from your update source (see [update handling](#update-handling)).

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




