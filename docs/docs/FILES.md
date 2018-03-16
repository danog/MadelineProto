# Uploading and downloading files

MadelineProto provides wrapper methods to upload and download files that support bot API file ids.

## Uploading files

To upload and send media, first you need to get an [InputFile](https://daniil.it/MadelineProto/API_docs/types/InputFile.html) (for nornal chats) or an [InputEncryptedFile](https://daniil.it/MadelineProto/API_docs/types/InputFile.html) (for secret chats):

```
$InputFile = $
```

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
