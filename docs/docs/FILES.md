# Uploading and downloading files

MadelineProto provides wrapper methods to upload and download files that support bot API file ids.

## Sending files

To send photos and documents to someone, use the [$MadelineProto->messages->sendMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html) method, click on the link for more info.

The required `message` parameter is the caption: it can contain URLs, mentions, bold and italic text, thanks to the `parse_mode` parameter, that enables markdown or HTML parsing.

The `media` parameter contains the file path and other info about the file.

It can contain [lots of various objects](https://docs.madelineproto.xyz/API_docs/types/InputMedia.html), here are the most important:

### [inputMediaUploadedPhoto](https://docs.madelineproto.xyz/API_docs/constructors/inputMediaUploadedPhoto.html)
```
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
```
$sentMessage = $MadelineProto->messages->sendMedia([
    'peer' => '@danogentili',
    'media' => [
        '_' => 'inputMediaUploadedDocument',
        'file' => 'faust.jpg'
        'attributes' => [
            ['_' => 'documentAttributeVideo', 'round_message' => false, 'supports_streaming' => true, 'duration' => 45, 'w' => 1920, 'h' => 1080]
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

* [documentAttributeFilename](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeFilename.html) to send a document
* [documentAttributeImageSize](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeImageSize.html) to send a photo as document
* [documentAttributeAnimated](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeAnimated.html) to send a gif
* [documentAttributeVideo](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeVideo.html) to send a video
* [documentAttributeAudio](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeAudio.html) to send an audio file
* [documentAttributeAudio](https://docs.madelineproto.xyz/API_docs/constructors/documentAttributeAudio.html) with voice = true to send a voice message

You can set all of the w, h, duration parameters to 0, telegram should detect them automatically.

### [inputMediaDocument](https://docs.madelineproto.xyz/API_docs/constructors/inputMediaDocument.html)


## Uploading files

```
$sentMessage = $MadelineProto->messages->uploadMedia([
    'media' => [
        '_' => 'inputMediaUploadedPhoto',
        'file' => 'faust.jpg'
    ],
]);
```

You can also only upload a file, without actually sending it to anyone, storing only the file ID for later usage.
The [$MadelineProto->messages->uploadMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_uploadMedia.html) function is a reduced version of the [$MadelineProto->messages->sendMedia](https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html), that requires only a `media` parameter, with the media to upload.


Every method described in this section accepts a last optional paramater with a callable function that will be called during the upload/download using the first parameter to pass a floating point number indicating the upload/download status in percentage.  

The upload method returns an [InputFile](https://docs.madelineproto.xyz/API_docs/types/InputFile.html) object that must be used to generate an [InputMedia](https://docs.madelineproto.xyz/API_docs/types/InputMedia.html) object, that can be later sent using the [sendmedia method](https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html).  

The `upload_encrypted` method returns an [InputEncryptedFile](https://docs.madelineproto.xyz/API_docs/types/InputEncryptedFile.html) object that must be used to generate an [EncryptedMessage](https://docs.madelineproto.xyz/API_docs/types/EncryptedMessage.html) object, that can be later sent using the [sendEncryptedFile method](https://docs.madelineproto.xyz/API_docs/methods/messages_sendEncryptedFile.html).  


```
$inputFile = $MadelineProto->upload('file', 'optional new file name.ext');
// Generate an inputMedia object and store it in $inputMedia, see tests/testing.php
$MadelineProto->messages->sendMedia(['peer' => '@pwrtelegramgroup', 'media' => $inputMedia]);

$inputEncryptedFile = $MadelineProto->upload_encrypted('file', 'optional new file name.ext');

```

To convert the result of sendMedia to a bot API file id select the messageMedia object from the output of the method and pass it to `$MadelineProto->API->MTProto_to_botAPI()`.  

See tests/testing.php for more examples.


There are multiple download methods that allow you to download a file to a directory, to a file or to a stream.  
The first parameter of these functions must always be either a [messageMediaPhoto](https://docs.madelineproto.xyz/API_docs/constructors/messageMediaPhoto.html) or a [messageMediaDocument](https://docs.madelineproto.xyz/API_docs/constructors/messageMediaDocument.html) object, an [EncryptedMessage](https://docs.madelineproto.xyz/API_docs/types/EncryptedMessage.html) or a bot API file id. These objects are usually received in updates, see `bot.php` for examples


```
$output_file_name = $MadelineProto->download_to_dir($message_media, '/tmp/dldir');
$custom_output_file_name = $MadelineProto->download_to_file($message_media, '/tmp/dldir/customname.ext');
$stream = fopen('php://output', 'w'); // Stream to browser like with echo
$MadelineProto->download_to_stream($message_media, $stream, $cb, $offset, $endoffset); // offset and endoffset are optional parameters that specify the byte from which to start downloading and the byte where to stop downloading (the latter non-inclusive), if not specified default to 0 and the size of the file
```
