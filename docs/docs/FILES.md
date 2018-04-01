# Uploading and downloading files

MadelineProto provides wrapper methods to upload and download files that support bot API file ids.

Maximum file size is of 1.5 GB.

* [Uploading & sending files](#sending-files)
  * [Security notice](#security-notice)
  * [Photos](#inputmediauploadedphoto)
  * [Documents](#inputmediauploadeddocument)
    * [Documents](#documentattributefilename-to-send-a-document)
    * [Photos as documents](#documentattributeimagesize-to-send-a-photo-as-document)
    * [GIFs](#documentattributeanimated-to-send-a-gif)
    * [Videos](#documentattributevideo-to-send-a-video)
    * [Audio & Voice](#documentattributeaudio-to-send-an-audio-file)
* [Uploading files](#uploading-files)
* [Bot API file IDs](#bot-api-file-ids)
* [Reusing uploaded files](#reusing-uploaded-files)
* [Downloading files](#downloading-files)
  * [Extracting download info](#extracting-download-info)
  * [Download to directory](#download-to-directory)
  * [Download to file](#download-to-file)
  * [Download to browser (streaming)](#download-to-browser-with-streams)
* [Getting progress](#getting-progress)

## Sending files

To send photos and documents to someone, use the [$MadelineProto->messages->sendMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html) method, click on the link for more info.

The required `message` parameter is the caption: it can contain URLs, mentions, bold and italic text, thanks to the `parse_mode` parameter, that enables markdown or HTML parsing.

The `media` parameter contains the file path and other info about the file.

It can contain [lots of various objects](https://docs.madelineproto.xyz/API_docs/types/InputMedia.html), here are the most important:

### Security notice

Be careful when calling methods with user-provided parameters: the upload function may be used to access and send any file.  
To disable automatic uploads by file name, set `$MadelineProto->settings['upload']['allow_automatic_upload'] = false` and upload files [manually](#reusing-uploaded-files).


### [inputMediaUploadedPhoto](https://docs.madelineproto.xyz/API_docs/constructors/inputMediaUploadedPhoto.html)
```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedPhoto',
        'file' => 'faust.jpg'
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

Can be used to upload photos: simply provide the photo's file path in the `file` field, and optionally provide a `ttl_seconds` field to set the self-destruction period of the photo, even for normal chats

### [inputMediaUploadedDocument](https://docs.madelineproto.xyz/API_docs/constructors/inputMediaUploadedDocument.html)
```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'video.mp4',
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true]
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

Can be used to upload documents, videos, gifs, voice messages, round videos, round voice messages: simply provide the file's file path in the `file` field, and optionally provide a `ttl_seconds` field to set the self-destruction period of the photo, even for normal chats.  
You must also provide the file's mime type in the `mime_type` field, generate it using `mime_content_type($file_path);` (tip: try using an unexpected mime type to make official clients crash ;).  
Use the `nosound_video` field if the video does not have sound (gifs).  
To actually set the document type, provide one or more [DocumentAttribute](https://docs.madelineproto.xyz/API_docs/types/DocumentAttribute.html) objects to the `attributes` field:  

### [documentAttributeFilename](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeFilename.html) to send a document

```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'file.txt',
        'attributes' => [
            ['_' => 'documentAttributeFilename', 'file_name' => 'document.txt']
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

### [documentAttributeImageSize](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeImageSize.html) to send a photo as document

```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'file.jpg',
        'attributes' => [
            ['_' => 'documentAttributeImageSize'],
            ['_' => 'documentAttributeFilename', 'file_name' => 'image.jpg']
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

### [documentAttributeAnimated](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeAnimated.html) to send a gif
```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'file.mp4',
        'attributes' => [
            ['_' => 'documentAttributeAnimated']
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

### [documentAttributeVideo](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeVideo.html) to send a video
```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'video.mp4',
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true]
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

Set `round_message` to true to send a round message.  
You might want to manually provide square `w` (width) and `h` (height) parameters to send round videos.


### [documentAttributeAudio](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeAudio.html) to send an audio file

```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'song.mp3',
        'attributes' => [
            ['_' => 'documentAttributeAudio', 'voice' => false, 'title' => 'This is magic', 'performer' => 'Daniil Gentili']
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

Set the `voice` parameter to true to send a voice message.


## Uploading files

```php
$MessageMedia = $MadelineProto->messages->uploadMedia([
    'media' => [
        '_' => 'inputMediaUploadedPhoto',
        'file' => 'faust.jpg'
    ],
]);
```

You can also only upload a file, without actually sending it to anyone, storing only the file ID for later usage.

The [$MadelineProto->messages->uploadMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_uploadMedia.html) function is a reduced version of the [$MadelineProto->messages->sendMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html), that requires only a `media` parameter, with the media to upload.  

The returned [MessageMedia](https://docs.madelineproto.xyz/API_docs/types/MessageMedia.html) object can then be reused to resend the document using sendMedia.

```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => $MessageMedia,
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

`$MessageMedia` can also be a [Message](https://docs.madelineproto.xyz/API_docs/types/Message.html) (the media contained in the message will be sent), an [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html) (the media contained in the message contained in the update will be sent).

## Bot API file IDs

`$MessageMedia` can even be a bot API file ID, generated by the bot API, or by MadelineProto:

Actual MessageMedia objects can also be converted to bot API file IDs like this:

```php
$botAPI_file = $MadelineProto->MTProto_to_botAPI($MessageMedia);
```

`$botAPI_file` now contains a [bot API message](https://core.telegram.org/bots/api#message), to extract the file ID from it use the following code:

```php
foreach (['audio', 'document', 'photo', 'sticker', 'video', 'voice', 'video_note'] as $type) {
    if (isset($botAPI_file[$type]) && is_array($botAPI_file[$type])) {
        $method = $type;
    }
}
$result['file_type'] = $method;
if ($result['file_type'] == 'photo') {
    $result['file_size'] = $botAPI_file[$method][0]['file_size'];
    if (isset($botAPI_file[$method][0]['file_name'])) {
        $result['file_name'] = $botAPI_file[$method][0]['file_name'];
        $result['file_id'] = $botAPI_file[$method][0]['file_id'];
    }
} else {
    if (isset($botAPI_file[$method]['file_name'])) {
        $result['file_name'] = $botAPI_file[$method]['file_name'];
    }
    if (isset($botAPI_file[$method]['file_size'])) {
        $result['file_size'] = $botAPI_file[$method]['file_size'];
    }
    if (isset($botAPI_file[$method]['mime_type'])) {
        $result['mime_type'] = $botAPI_file[$method]['mime_type'];
    }
    $result['file_id'] = $botAPI_file[$method]['file_id'];
}
if (!isset($result['mime_type'])) {
    $result['mime_type'] = 'application/octet-stream';
}
if (!isset($result['file_name'])) {
    $result['file_name'] = $result['file_id'].($method === 'sticker' ? '.webp' : '');
}
```

* `$result['file_id']` - Bot API file ID
* `$result['mime_type']` - Mime type
* `$result['file_type']` - File type: voice, video, video_note (round video), music, video, photo, sticker or document
* `$result['file_size']` - File size
* `$result['file_name']` - File name

## Reusing uploaded files

`$MadelineProto->messages->uploadMedia` and bot API file IDs do not allow you to modify the type of the file to send: however, MadelineProto provides a method that can generate a file object that can be resent with multiple file types.

```php
$inputFile = $MadelineProto->upload('filename.mp4');
```

The generated `$inputFile` can later be reused thusly:

```php
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => $inputFile,
        'attributes' => [
            ['_' => 'documentAttributeFilename', 'file_name' => 'video.mp4']
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
$sentMessageVideo = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => $inputFile,
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true]
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);
```

In this case, we're reusing the same InputFile to send both a document and a video, without uploading the file twice.

The concept is easy: where you would usually provide a file path, simply provide `$inputFile`.


## Downloading files

There are multiple download methods that allow you to download a file to a directory, to a file or to a stream.  

### Extracting download info
```php
$info = $MadelineProto->get_download_info($MessageMedia);
```

`$MessageMedia` can be a [MessageMedia](https://docs.madelineproto.xyz/API_docs/types/MessageMedia.html) object or a bot API file ID.

* `$info['ext']` - The file extension
* `$info['name']` - The file name, without the extension
* `$info['mime']` - The file mime type
* `$info['size']` - The file size

### Download to directory
```php
$output_file_name = $MadelineProto->download_to_dir($MessageMedia, '/tmp/');
```

This downloads the given file to `/tmp`, and returns the full generated file path.

`$MessageMedia` can be either a [Message](https://docs.madelineproto.xyz/API_docs/types/Message.html), an [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html), a [MessageMedia](https://docs.madelineproto.xyz/API_docs/types/MessageMedia.html) object, or a bot API file ID.

### Download to file
```php
$output_file_name = $MadelineProto->download_to_file($MessageMedia, '/tmp/myname.mp4');
```

This downloads the given file to `/tmp/myname.mp4`, and returns the full file path.

`$MessageMedia`can be either a [Message](https://docs.madelineproto.xyz/API_docs/types/Message.html), an [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html), a [MessageMedia](https://docs.madelineproto.xyz/API_docs/types/MessageMedia.html) object, or a bot API file ID.


### Download to browser with streams
```php
$info = $MadelineProto->get_download_info($MessageMedia);
header('Content-Length: '.$info['size']);
header('Content-Type: '.$info['mime']);

$stream = fopen('php://output', 'w');
$MadelineProto->download_to_stream($MessageMedia, $stream, $cb, $offset, $endoffset);
```

This downloads the given file to the browser, sending also information about the file's type and size.

`$MessageMedia` can be either a [Message](https://docs.madelineproto.xyz/API_docs/types/Message.html), an [Update](https://docs.madelineproto.xyz/API_docs/types/Update.html), a [MessageMedia](https://docs.madelineproto.xyz/API_docs/types/MessageMedia.html) object, or a bot API file ID.

`$stream` must be a writeable stream

`$cb` is an optional parameter can be a callback for download progress, but it shouldn't be used, the new [FileCallback](#getting-progress) should be used instead

`$offset` is an optional parameter that specifies the byte from which to start downloading 

`$limit` is an optional parameter that specifies the byte where to stop downloading (non-inclusive)


## Getting progress

To get the upload/download progress in real-time, use the `\danog\MadelineProto\FileCallback` class:

```php
$peer = '@danogentili';
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => $peer,
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => new \danog\MadelineProto\FileCallback(
            'video.mp4',
            function ($progress) use ($MadelineProto, $peer) {
                $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => 'Upload progress: '.$progress.'%']);
            }
        ),
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true]
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);

$output_file_name = $MadelineProto->download_to_file(
    new \danog\MadelineProto\FileCallback(
        $sentMessage,
        function ($progress) use ($MadelineProto, $peer) {
            $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => 'Download progress: '.$progress.'%']);
        }
    ),
    '/tmp/myname.mp4'
);
```

This will send the file `video.mp4` to [@danogentili](https://t.me/danogentili): while uploading, he will receive progress messages `Upload progress: 24%` until the upload is complete; while uploading, he will receive progress messages `Download progress: 34%` until the download is complete.

A FileCallback object can be provided to `uploadMedia`, `sendMedia`, `uploadProfilePicture`, `upload`, `upload_encrypted`, `download_to_*`: the first parameter to its constructor must be the file path/object that is usually accepted by the function, the second must be a callable function or object.

You can also write your own callback class, just implement `\danog\MadelineProto\FileCallbackInterface`:  
```php
class MyCallback implements \danog\MadelineProto\FileCallbackInterface
{
    private $file;
    private $peer;
    private $MadelineProto;
    public function __construct($file, $peer, $MadelineProto)
    {
        $this->file = $file;
        $this->peer = $peer;
        $this->MadelineProto = $MadelineProto;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function __invoke($progress)
    {
        $this->MadelineProto->messages->sendMessage(['peer' => $this->peer, 'message' => 'Progress: '.$progress.'%']);
    }
}
$peer = '@danogentili';
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => $peer,
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => new MyCallback('video.mp4', $peer, $MadelineProto),
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true]
        ]
    ],
    'message' => '[This is the caption](https://t.me/MadelineProto)',
    'parse_mode' => 'Markdown'
]);

$output_file_name = $MadelineProto->download_to_file(
    new MyCallback($sentMessage, $peer, $MadelineProto),
    '/tmp/myname.mp4'
);
```

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/USING_METHODS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/CHAT_INFO.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>