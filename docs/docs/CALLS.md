# Calls

```php
if (!file_exists('input.raw')) {
    echo 'Downloading example song'.PHP_EOL;
    copy('https://github.com/danog/MadelineProto/raw/master/input.raw', 'input.raw');
}
$call = $MadelineProto->request_call('@danogentili')->play('input.raw')->then('input.raw')->playOnHold(['input.raw'])->setOutputFile('output.raw');
```

MadelineProto provides an easy wrapper to work with phone calls.

The wrapper consists in the `\danog\MadelineProto\VoIP` class, that can be installed by compiling the [php-libtgvoip](https://voip.madelineproto.xyz) extension.

* Please read the whole [VoIP API documentation](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) before proceeding.
* [Requesting a call](#requesting-a-call)
  * [Playing mp3 files](#playing-mp3-files)
  * [Playing streams](#playing-streams)
  * [Changing audio quality](#changing-audio-quality)
* [Putting it all together](#putting-it-all-together)
* [Accepting calls](#accepting-calls)


## Requesting a call
```php
$call = $MadelineProto->request_call('@danogentili');
```

The [request_call](https://docs.madelineproto.xyz/request_call.html) function accepts one parameter with the ID/username/Peer/User/InputPeer of the person to call, and returns a VoIP object that can be used to play audio files, set the hold files, change the configuration and set the output file (see the [VoIP API documentation](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) for more info).

MadelineProto works using raw signed PCM audio with the sample rate and the bit depth specified in the configuration (see [here](https://docs.madelineproto.xyz/API_docs/types/PhoneCall.html) for info on how to fetch it): usually it's 1 channel, sample rate of 48khz, codec PCM s16 little endian.


## Playing mp3 files

Input/output audio can be converted from/to any audio/video file using ffmpeg:

```bash
ffmpeg -i anyaudioorvideo.mp3 -f s16le -ac 1 -ar 48000 -acodec pcm_s16le mysong.raw
```

## Playing streams

You can also play streams:

```bash
mkfifo mystream.raw
ffmpeg -i http://icestreaming.rai.it/1.mp3 -f s16le -ac 1 -ar 48000 -acodec pcm_s16le pipe:1 > mystream.raw
```

Remember: you can only play one fifo at a time. If you want to play the same stream in multiple calls, you must duplicate the data written to the fifo, by writing it to another fifo.


## Changing audio quality

The best way to raise the bitrate is to let libtgvoip do it automatically, based on network conditions.  
However, the usual outgoing bitrate used by telegram for ethernet networks is around 20kbps. That is clearly not enough.  
To increase it, you must modify the shared settings:  

```php
$controller->configuration['shared_config']['audio_init_bitrate'] = 60 * 1000; // Audio bitrate set when the call is started
$controller->configuration['shared_config']['audio_max_bitrate']  => 70 * 1000; // Maximum audio bitrate
$controller->configuration['shared_config']['audio_min_bitrate']  => 15 * 1000; // Minimum audio bitrate
$controller->configuration['shared_config']['audio_bitrate_step_decr']  => 1000; // Decreasing step: when libtgvoip has to lower the bitrate, it decreases it `audio_bitrate_step_decr` bps at a time
$controller->configuration['shared_config']['audio_bitrate_step_incr']  => 1000; // Increasing step: when libtgvoip has to make the bitrate higher, it increases it `audio_bitrate_step_decr` bps at a time
$controller->parseConfig();
```

Do this before accepting a call or right after requesting one.  
It is best to set an initial bitrate not much smaller than the maximum bitrate, since libtgvoip tends to always increase it, initially, and when it does that, slight sound distortions occur.  
The maximum bitrate can be set to any value, really, as long as it's smaller than `110*1000` (temporary limit that I'm trying to work around).  
It is best to set the minumum bitrate to a value smaller than or equal to `20*1000`.  
Of course, you can't set the initial bitrate to a value higher than the maximum bitrate or lower than the minimum bitrate, and you can't set the minimum bitrate to a value higher than the maximum bitrate, or the maximum bitrate to a value lower than the minimum bitrate.  
You can also tweak the steps to speed up/slow down automatic increasing/decreasing of the bitrates, or stop it altogether by settings both steps to 0 (in this case, the bitrate will always be equal to the initial bitrate).  
Try to find out what works best for you.  
If you manually set the network type to `NET_TYPE_GPRS`, `NET_TYPE_EDGE`, or enabled data saving mode, you must append a `_gprs`, `_edge`, `_saving` to the audio_init/max/min bitrate keys.  


## Putting it all together

Requesting calls is easy, just run the `request_call` method.

```php
$controller = $MadelineProto->request_call('@danogentili')->play('input.raw')->then('inputb.raw')->playOhHold(['hold.raw'])->setOutputFile('output.raw');
$controller->configuration['log_file_path'] = $controller->getOtherID().'.log';

// We need to receive updates in order to know that the other use accepted the call
while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_READY) {
    $MadelineProto->get_updates();
}

```

## Accepting calls

Accepting calls is just as easy: you will receive an [updatePhoneCall](https://docs.madelineproto.xyz/API_docs/constructors/updatePhoneCall.html) object from your update source (see [update handling](#update-handling)).

This array will contain a VoIP object under the `phone_call` key.

```php
$updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
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

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/INLINE_BUTTONS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/SECRET_CHATS.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>