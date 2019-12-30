# MadelineProto, a PHP MTProto telegram client

Created by <a href="https://daniil.it" target="_blank" rel="noopener">Daniil Gentili</a>

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto) and the [support groups](https://t.me/pwrtelegramgroup)!

[Now with Telegram TON blockchain integration](https://github.com/danog/MadelineProto/blob/master/ton/README.md)!

<a href="https://telegram.org/apps" target="_blank" rel="noopener">Approved by Telegram!</a>

## What's this?

This library can be used to easily interact with Telegram **without** the bot API, just like the official apps.

It can login with a phone number (MTProto API), or with a bot token (MTProto API, **no bot API involved!**).

[It is now fully async](https://docs.madelineproto.xyz/docs/ASYNC.html)!

## Getting started ([now fully async!](https://docs.madelineproto.xyz/docs/ASYNC.html))

```php
<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();

    $me = yield $MadelineProto->getSelf();

    $MadelineProto->logger($me);

    if (!$me['bot']) {
        yield $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
        yield $MadelineProto->channels->joinChannel(['channel' => '@MadelineProto']);

        try {
            yield $MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $MadelineProto->logger($e);
        }

        yield $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg', 'message' => 'Testing MadelineProto!']);
    }
    yield $MadelineProto->echo('OK, done!');
});
```

[Try this code now!](https://try.madelineproto.xyz) or run this code in a browser or in a console. 


Tip: if you receive an error (or nothing), [send us](https://t.me/pwrtelegramgroup) the error message and the `MadelineProto.log` file that was created in the same directory (if running from a browser).  

## Documentation

* [Creating a client](https://docs.madelineproto.xyz/docs/CREATING_A_CLIENT.html)
* [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)
  * [Getting permission to use the telegram API](https://docs.madelineproto.xyz/docs/LOGIN.html#getting-permission-to-use-the-telegram-api)
  * [Automatic](https://docs.madelineproto.xyz/docs/LOGIN.html#automatic-now-fully-async)
  * [Manual (user)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-user)
    * [API ID](https://docs.madelineproto.xyz/docs/LOGIN.html#api-id)
  * [Manual (bot)](https://docs.madelineproto.xyz/docs/LOGIN.html#manual-bot)
  * [Logout](https://docs.madelineproto.xyz/docs/LOGIN.html#logout)
  * [Changing 2FA password](https://docs.madelineproto.xyz/docs/LOGIN.html#changing-2fa-password)
* [Features](https://docs.madelineproto.xyz/docs/FEATURES.html)
* [Requirements](https://docs.madelineproto.xyz/docs/REQUIREMENTS.html)
* [Installation](https://docs.madelineproto.xyz/docs/INSTALLATION.html)
  * [Simple](https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple)
  * [Simple (manual)](https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple-manual)
  * [Composer from scratch](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-scratch)
  * [Composer from existing project](https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-existing-project)
* [Handling updates (new messages)](https://docs.madelineproto.xyz/docs/UPDATES.html)
  * [Self-restart on webhosts](https://docs.madelineproto.xyz/docs/UPDATES.html#self-restart-on-webhosts)
  * [Async Event driven](https://docs.madelineproto.xyz/docs/UPDATES.html#async-event-driven)
  * [Multi-account: Async Combined Event driven update handling](https://docs.madelineproto.xyz/docs/UPDATES.html#async-combined-event-driven)
  * [Async Callback](https://docs.madelineproto.xyz/docs/UPDATES.html#async-callback)
  * [Noop (default)](https://docs.madelineproto.xyz/docs/UPDATES.html#noop)
  * [Fetch all updates from the beginning](https://docs.madelineproto.xyz/docs/UPDATES.html#fetch-all-updates-from-the-beginning)
* [Settings](https://docs.madelineproto.xyz/docs/SETTINGS.html)
* [Getting info about the current user](https://docs.madelineproto.xyz/docs/SELF.html)
* [Exceptions](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html)
  * [List of exception types](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#list-of-exception-types)
  * [Pretty TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#pretty-tl-trace)
  * [Getting the TL trace](https://docs.madelineproto.xyz/docs/EXCEPTIONS.html#getting-the-tl-trace)
* [Avoiding FLOOD_WAITs](https://docs.madelineproto.xyz/docs/FLOOD_WAIT.html)
* [Logging](https://docs.madelineproto.xyz/docs/LOGGING.html)
* [Telegram VoIP phone calls](https://docs.madelineproto.xyz/docs/CALLS.html)
  * [Requesting a call](https://docs.madelineproto.xyz/docs/CALLS.html#requesting-a-call)
    * [Playing mp3 files](https://docs.madelineproto.xyz/docs/CALLS.html#playing-mp3-files)
    * [Playing streams](https://docs.madelineproto.xyz/docs/CALLS.html#playing-streams)
    * [Changing audio quality](https://docs.madelineproto.xyz/docs/CALLS.html#changing-audio-quality)
  * [Putting it all together](https://docs.madelineproto.xyz/docs/CALLS.html#putting-it-all-together)
  * [Accepting calls](https://docs.madelineproto.xyz/docs/CALLS.html#accepting-calls)
* [Uploading and downloading files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Bot API file IDs](https://docs.madelineproto.xyz/docs/FILES.html#bot-api-file-ids)
  * [Uploading & sending files](https://docs.madelineproto.xyz/docs/FILES.html#sending-files)
    * [Security notice](https://docs.madelineproto.xyz/docs/FILES.html#security-notice)
    * [Photos](https://docs.madelineproto.xyz/docs/FILES.html#inputmediauploadedphoto)
    * [Documents](https://docs.madelineproto.xyz/docs/FILES.html#inputmediauploadeddocument)
      * [Documents](https://docs.madelineproto.xyz/docs/FILES.html#documentattributefilename-to-send-a-document)
      * [Photos as documents](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeimagesize-to-send-a-photo-as-document)
      * [GIFs](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeanimated-to-send-a-gif)
      * [Videos](https://docs.madelineproto.xyz/docs/FILES.html#documentattributevideo-to-send-a-video)
      * [Audio & Voice](https://docs.madelineproto.xyz/docs/FILES.html#documentattributeaudio-to-send-an-audio-file)
  * [Uploading files](https://docs.madelineproto.xyz/docs/FILES.html#uploading-files)
  * [Reusing uploaded files](https://docs.madelineproto.xyz/docs/FILES.html#reusing-uploaded-files)
  * [Renaming files](https://docs.madelineproto.xyz/docs/FILES.html#renaming-files)
  * [Downloading files](https://docs.madelineproto.xyz/docs/FILES.html#downloading-files)
    * [Extracting download info](https://docs.madelineproto.xyz/docs/FILES.html#extracting-download-info)
    * [Downloading profile pictures](https://docs.madelineproto.xyz/docs/FILES.html#downloading-profile-pictures)
    * [Download to directory](https://docs.madelineproto.xyz/docs/FILES.html#download-to-directory)
    * [Download to file](https://docs.madelineproto.xyz/docs/FILES.html#download-to-file)
    * [Download to stream](https://docs.madelineproto.xyz/docs/FILES.html#download-to-stream)
    * [Download to callback](https://docs.madelineproto.xyz/docs/FILES.html#download-to-callback)
    * [Download to browser (streaming)](https://docs.madelineproto.xyz/docs/FILES.html#download-to-browser-with-streams)
  * [Getting progress](https://docs.madelineproto.xyz/docs/FILES.html#getting-progress)
* [Getting info about chats](https://docs.madelineproto.xyz/docs/CHAT_INFO.html)
  * [Full chat info with full list of participants](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_pwr_chat-now-fully-async)
  * [Full chat info](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_full_info-now-fully-async)
  * [Reduced chat info (very fast)](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_info-now-fully-async)
  * [Just the chat ID (extremely fast)](https://docs.madelineproto.xyz/docs/CHAT_INFO.html#get_id-now-fully-async)
* [Getting all chats (dialogs)](https://docs.madelineproto.xyz/docs/DIALOGS.html)
  * [Dialog list](https://docs.madelineproto.xyz/docs/DIALOGS.html#get_dialogs-now-fully-async)
  * [Full dialog info](https://docs.madelineproto.xyz/docs/DIALOGS.html#get_full_dialogs-now-fully-async)
* [Inline buttons ([now fully async!](https://docs.madelineproto.xyz/docs/ASYNC.html))](https://docs.madelineproto.xyz/docs/INLINE_BUTTONS.html)
* [Secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
  * [Requesting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#requesting-secret-chats-now-fully-async)
  * [Accepting secret chats](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#accepting-secret-chats-now-fully-async)
  * [Checking secret chat status](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#checking-secret-chat-status-now-fully-async)
  * [Sending secret messages](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html#sending-secret-messages-now-fully-async)
* [Lua binding](https://docs.madelineproto.xyz/docs/LUA.html)
* [Using a proxy](https://docs.madelineproto.xyz/docs/PROXY.html)
  * [How to set a proxy](https://docs.madelineproto.xyz/docs/PROXY.html#how-to-set-a-proxy)
  * [Multiple proxies with automatic switch](https://docs.madelineproto.xyz/docs/PROXY.html#multiple-proxies-with-automatic-switch)
  * [Use pre-built MTProxy](https://docs.madelineproto.xyz/docs/PROXY.html#mtproxy)
  * [Use pre-built Socks5 proxy](https://docs.madelineproto.xyz/docs/PROXY.html#socks5-proxy)
  * [Use pre-built HTTP proxy](https://docs.madelineproto.xyz/docs/PROXY.html#http-proxy)
  * [Build your own proxy](https://docs.madelineproto.xyz/docs/PROXY.html#build-your-proxy)
* [Async](https://docs.madelineproto.xyz/docs/ASYNC.html)
  * [Usage](https://docs.madelineproto.xyz/docs/ASYNC.html#usage)
    * [Enabling the MadelineProto async API](https://docs.madelineproto.xyz/docs/ASYNC.html#enabling-the-madelineproto-async-api)
    * [Using the MadelineProto async API](https://docs.madelineproto.xyz/docs/ASYNC.html#using-the-madelineproto-async-api)
      * [Async in event handler](https://docs.madelineproto.xyz/docs/ASYNC.html#async-in-event-handler)
      * [Async in callback handler](https://docs.madelineproto.xyz/docs/ASYNC.html#async-in-callback-handler)
      * [Wrapped async](https://docs.madelineproto.xyz/docs/ASYNC.html#wrapped-async)
      * [Multiple async](https://docs.madelineproto.xyz/docs/ASYNC.html#multiple-async)
      * [ArrayAccess async](https://docs.madelineproto.xyz/docs/ASYNC.html#arrayaccess-async)
      * [Ignored async](https://docs.madelineproto.xyz/docs/ASYNC.html#ignored-async)
      * [Blocking async](https://docs.madelineproto.xyz/docs/ASYNC.html#blocking-async)
    * [MadelineProto and AMPHP async APIs](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-and-amphp-async-apis)
      * [Helper methods](https://docs.madelineproto.xyz/docs/ASYNC.html#helper-methods)
        * [Async sleep](https://docs.madelineproto.xyz/docs/ASYNC.html#async-sleep-does-not-block-the-main-thread)
        * [Async readline](https://docs.madelineproto.xyz/docs/ASYNC.html#async-readline-does-not-block-the-main-thread)
        * [Async echo](https://docs.madelineproto.xyz/docs/ASYNC.html#async-echo-does-not-block-the-main-thread)
        * [MadelineProto artax HTTP client](https://docs.madelineproto.xyz/docs/ASYNC.html#madelineproto-artax-http-client)
        * [Async forking](https://docs.madelineproto.xyz/docs/ASYNC.html#async-forking-does-green-thread-forking)
        * [Async flock](https://docs.madelineproto.xyz/docs/ASYNC.html#async-flock)
        * [Combining async operations](https://docs.madelineproto.xyz/docs/ASYNC.html#combining-async-operations)
      * [MadelineProto async loop APIs](https://docs.madelineproto.xyz/docs/ASYNC.html#async-loop-apis)
        * [Loop](https://docs.madelineproto.xyz/docs/ASYNC.html#loop)
        * [ResumableLoop](https://docs.madelineproto.xyz/docs/ASYNC.html#resumableloop)
        * [SignalLoop](https://docs.madelineproto.xyz/docs/ASYNC.html#signalloop)
        * [ResumableSignalLoop](https://docs.madelineproto.xyz/docs/ASYNC.html#resumablesignalloop)
        * [GenericLoop](https://docs.madelineproto.xyz/docs/ASYNC.html#genericloop)
* [Using methods](https://docs.madelineproto.xyz/docs/USING_METHODS.html)
  * [FULL API Documentation with descriptions](https://docs.madelineproto.xyz/API_docs/methods/)
    * [Logout](https://docs.madelineproto.xyz/logout.html)
    * [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)
    * [Change 2FA password](https://docs.madelineproto.xyz/update2fa.html)
    * [Get all chats, broadcast a message to all chats](https://docs.madelineproto.xyz/docs/DIALOGS.html)
    * [Get the full participant list of a channel/group/supergroup](https://docs.madelineproto.xyz/getPwrChat.html)
    * [Get full info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/getFullInfo.html)
    * [Get info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/getInfo.html)
    * [Get info about the currently logged-in user](https://docs.madelineproto.xyz/getSelf.html)
    * [Upload or download files up to 1.5 GB](https://docs.madelineproto.xyz/docs/FILES.html)
    * [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)
    * [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.acceptTermsOfService.html" name="help.acceptTermsOfService">Accept the new terms of service: help.acceptTermsOfService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveGif.html" name="messages.saveGif">Add GIF to saved gifs list: messages.saveGif</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.addStickerToSet.html" name="stickers.addStickerToSet">Add a sticker to a stickerset, bots only. The sticker set must have been created by the bot: stickers.addStickerToSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.addContact.html" name="contacts.addContact">Add an existing telegram user as contact: contacts.addContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveRecentSticker.html" name="messages.saveRecentSticker">Add/remove sticker from recent stickers list: messages.saveRecentSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.addChatUser.html" name="messages.addChatUser">Adds a user to a chat and sends a service message on it: messages.addChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.block.html" name="contacts.block">Adds the user to the blacklist: contacts.block</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineBotResults.html" name="messages.setInlineBotResults">Answer an inline query, for bots only: messages.setInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.answerWebhookJSONQuery.html" name="bots.answerWebhookJSONQuery">Answers a custom query; for bots only: bots.answerWebhookJSONQuery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setDiscussionGroup.html" name="channels.setDiscussionGroup">Associate a group to a channel as discussion group for that channel: channels.setDiscussionGroup</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setStickers.html" name="channels.setStickers">Associate a stickerset to the supergroup: channels.setStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editBanned.html" name="channels.editBanned">Ban/unban/kick a user in a supergroup/channel: channels.editBanned</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.cancelPasswordEmail.html" name="account.cancelPasswordEmail">Cancel the code that was sent to verify an email to use as 2FA recovery method: account.cancelPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.cancelCode.html" name="auth.cancelCode">Cancel the login verification code: auth.cancelCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatTitle.html" name="messages.editChatTitle">Chanages chat name and sends a service message on it: messages.editChatTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveAutoDownloadSettings.html" name="account.saveAutoDownloadSettings">Change media autodownload settings: account.saveAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setPrivacy.html" name="account.setPrivacy">Change privacy settings of current account: account.setPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.changePhone.html" name="account.changePhone">Change the phone number of the current account: account.changePhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editPhoto.html" name="channels.editPhoto">Change the photo of a channel/supergroup: channels.editPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.updateUsername.html" name="channels.updateUsername">Change the username of a supergroup/channel: channels.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatPhoto.html" name="messages.editChatPhoto">Changes chat photo and sends a service message on it: messages.editChatPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.changeStickerPosition.html" name="stickers.changeStickerPosition">Changes the absolute position of a sticker in the set to which it belongs; for bots only. The sticker set must have been created by the bot: stickers.changeStickerPosition</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateUsername.html" name="account.updateUsername">Changes username for the current user: account.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.checkUsername.html" name="channels.checkUsername">Check if a username is free and can be assigned to a channel/supergroup: channels.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.checkChatInvite.html" name="messages.checkChatInvite">Check the validity of a chat invite link and get basic info about it: messages.checkChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearAllDrafts.html" name="messages.clearAllDrafts">Clear all drafts: messages.clearAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearRecentStickers.html" name="messages.clearRecentStickers">Clear recent stickers: messages.clearRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.clearSavedInfo.html" name="payments.clearSavedInfo">Clear saved payment information: payments.clearSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPhone.html" name="account.confirmPhone">Confirm a phone number to cancel account deletion, for more info click here »: account.confirmPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.receivedMessages.html" name="messages.receivedMessages">Confirms receipt of messages by a client, cancels PUSH-notification sending: messages.receivedMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.createChannel.html" name="channels.createChannel">Create a supergroup/channel: channels.createChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.createStickerSet.html" name="stickers.createStickerSet">Create a stickerset, bots only: stickers.createStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.createTheme.html" name="account.createTheme">Create a theme: account.createTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadWallPaper.html" name="account.uploadWallPaper">Create and upload a new wallpaper: account.uploadWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.createChat.html" name="messages.createChat">Creates a new chat: messages.createChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteChannel.html" name="channels.deleteChannel">Delete a channel/supergroup: channels.deleteChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders.deleteFolder.html" name="folders.deleteFolder">Delete a folder: folders.deleteFolder</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteUserHistory.html" name="channels.deleteUserHistory">Delete all messages sent by a certain user in a supergroup: channels.deleteUserHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.dropTempAuthKeys.html" name="auth.dropTempAuthKeys">Delete all temporary authorization keys except for the ones specified: auth.dropTempAuthKeys</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteByPhones.html" name="contacts.deleteByPhones">Delete contacts by phone number: contacts.deleteByPhones</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWallPapers.html" name="account.resetWallPapers">Delete installed wallpapers: account.resetWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteMessages.html" name="channels.deleteMessages">Delete messages in a channel/supergroup: channels.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetSaved.html" name="contacts.resetSaved">Delete saved contacts: contacts.resetSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteScheduledMessages.html" name="messages.deleteScheduledMessages">Delete scheduled messages: messages.deleteScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteSecureValue.html" name="account.deleteSecureValue">Delete stored Telegram Passport documents, for more info see the passport docs »: account.deleteSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteHistory.html" name="channels.deleteHistory">Delete the history of a supergroup: channels.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteAccount.html" name="account.deleteAccount">Delete the user's account from the telegram servers. Can be used, for example, to delete the account of a user that provided the login code, but forgot the 2FA password and no recovery method is configured: account.deleteAccount</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.unregisterDevice.html" name="account.unregisterDevice">Deletes a device by its token, stops sending PUSH-notifications to it: account.unregisterDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteChatUser.html" name="messages.deleteChatUser">Deletes a user from a chat and sends a service message on it: messages.deleteChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteHistory.html" name="messages.deleteHistory">Deletes communication history: messages.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteMessages.html" name="messages.deleteMessages">Deletes messages by their identifiers: messages.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.deletePhotos.html" name="photos.deletePhotos">Deletes profile photos: photos.deletePhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteContacts.html" name="contacts.deleteContacts">Deletes several contacts from the list: contacts.deleteContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.unblock.html" name="contacts.unblock">Deletes the user from the blacklist: contacts.unblock</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editInlineBotMessage.html" name="messages.editInlineBotMessage">Edit an inline bot message: messages.editInlineBotMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editLocation.html" name="channels.editLocation">Edit location of geogroup: channels.editLocation</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editMessage.html" name="messages.editMessage">Edit message: messages.editMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders.editPeerFolders.html" name="folders.editPeerFolders">Edit peers in folder: folders.editPeerFolders</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatDefaultBannedRights.html" name="messages.editChatDefaultBannedRights">Edit the default banned rights of a channel/supergroup/group: messages.editChatDefaultBannedRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAbout.html" name="messages.editChatAbout">Edit the description of a group/supergroup/channel: messages.editChatAbout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editTitle.html" name="channels.editTitle">Edit the name of a channel/supergroup: channels.editTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateNotifySettings.html" name="account.updateNotifySettings">Edits notification settings from a given user/group, from all users/all groups: account.updateNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.toggleTopPeers.html" name="contacts.toggleTopPeers">Enable/disable top peers: contacts.toggleTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSignatures.html" name="channels.toggleSignatures">Enable/disable message signatures in channels: channels.toggleSignatures</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.exportChatInvite.html" name="messages.exportChatInvite">Export an invite link for a chat: messages.exportChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessageEditData.html" name="messages.getMessageEditData">Find out if a media message's caption can be edited: messages.getMessageEditData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.finishTakeoutSession.html" name="account.finishTakeoutSession">Finish account takeout session: account.finishTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.forwardMessages.html" name="messages.forwardMessages">Forwards messages by their IDs: messages.forwardMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getMessages.html" name="channels.getMessages">Get channel/supergroup messages: channels.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminedPublicChannels.html" name="channels.getAdminedPublicChannels">Get channels/supergroups/geogroups we're admin in. Usually called when the user exceeds the limit for owned public channels/supergroups/geogroups, and the user is given the choice to remove one of his channels/supergroups/geogroups: channels.getAdminedPublicChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPage.html" name="messages.getWebPage">Get instant view page: messages.getWebPage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getPassportConfig.html" name="help.getPassportConfig">Get passport configuration: help.getPassportConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDocumentByHash.html" name="messages.getDocumentByHash">Get a document by its SHA256 hash, mainly used for gifs: messages.getDocumentByHash</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getLeftChannels.html" name="channels.getLeftChannels">Get a list of channels/supergroups we left: channels.getLeftChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentForm.html" name="payments.getPaymentForm">Get a payment form: payments.getPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getArchivedStickers.html" name="messages.getArchivedStickers">Get all archived stickers: messages.getArchivedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllChats.html" name="messages.getAllChats">Get all chats, channels and supergroups: messages.getAllChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getSaved.html" name="contacts.getSaved">Get all contacts: contacts.getSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getGroupsForDiscussion.html" name="channels.getGroupsForDiscussion">Get all groups that can be used as discussion groups: channels.getGroupsForDiscussion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllStickers.html" name="messages.getAllStickers">Get all installed stickers: messages.getAllStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAllSecureValues.html" name="account.getAllSecureValues">Get all saved Telegram Passport documents, for more info see the passport docs »: account.getAllSecureValues</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessagesViews.html" name="messages.getMessagesViews">Get and increase the view counter of a message sent or forwarded from a channel: messages.getMessagesViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppConfig.html" name="help.getAppConfig">Get app-specific configuration: help.getAppConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsDifference.html" name="messages.getEmojiKeywordsDifference">Get changed emoji keywords: messages.getEmojiKeywordsDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppChangelog.html" name="help.getAppChangelog">Get changelog of current app: help.getAppChangelog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getCommonChats.html" name="messages.getCommonChats">Get chats in common with a user: messages.getCommonChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getCdnConfig.html" name="help.getCdnConfig">Get configuration for CDN file downloads: help.getCdnConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContactIDs.html" name="contacts.getContactIDs">Get contact by telegram IDs: contacts.getContactIDs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getLocated.html" name="contacts.getLocated">Get contacts near you: contacts.getLocated</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getOnlines.html" name="messages.getOnlines">Get count of online users in a chat: messages.getOnlines</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAccountTTL.html" name="account.getAccountTTL">Get days to live of account: account.getAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerDialogs.html" name="messages.getPeerDialogs">Get dialog info of specified peers: messages.getPeerDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogUnreadMarks.html" name="messages.getDialogUnreadMarks">Get dialogs manually marked as unread: messages.getDialogUnreadMarks</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFavedStickers.html" name="messages.getFavedStickers">Get faved stickers: messages.getFavedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFeaturedStickers.html" name="messages.getFeaturedStickers">Get featured stickers: messages.getFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineGameHighScores.html" name="messages.getInlineGameHighScores">Get highscores of a game sent using an inline bot: messages.getInlineGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getGameHighScores.html" name="messages.getGameHighScores">Get highscores of a game: messages.getGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getChannels.html" name="channels.getChannels">Get info about channels/supergroups: channels.getChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipant.html" name="channels.getParticipant">Get info about a channel/supergroup participant: channels.getParticipant</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getDeepLinkInfo.html" name="help.getDeepLinkInfo">Get info about a t.me link: help.getDeepLinkInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPaper.html" name="account.getWallPaper">Get info about a certain wallpaper: account.getWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickerSet.html" name="messages.getStickerSet">Get info about a stickerset: messages.getStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsLanguages.html" name="messages.getEmojiKeywordsLanguages">Get info about an emoji keyword localization: messages.getEmojiKeywordsLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguage.html" name="langpack.getLanguage">Get information about a language in a localization pack: langpack.getLanguage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguages.html" name="langpack.getLanguages">Get information about all languages in a localization pack: langpack.getLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMaskStickers.html" name="messages.getMaskStickers">Get installed mask stickers: messages.getMaskStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getThemes.html" name="account.getThemes">Get installed themes: account.getThemes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.exportMessageLink.html" name="channels.exportMessageLink">Get link and embed info of a message in a channel/supergroup: channels.exportMessageLink</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentLocations.html" name="messages.getRecentLocations">Get live location history of a certain user: messages.getRecentLocations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLangPack.html" name="langpack.getLangPack">Get localization pack strings: langpack.getLangPack</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywords.html" name="messages.getEmojiKeywords">Get localized emoji keywords: messages.getEmojiKeywords</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupportName.html" name="help.getSupportName">Get localized name of the telegram support user: help.getSupportName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizations.html" name="account.getAuthorizations">Get logged-in sessions: account.getAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAutoDownloadSettings.html" name="account.getAutoDownloadSettings">Get media autodownload settings: account.getAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSplitRanges.html" name="messages.getSplitRanges">Get message ranges for saving the user's chat history: messages.getSplitRanges</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestUrlAuth.html" name="messages.requestUrlAuth">Get more info about a Seamless Telegram Login authorization request, for more info click here »: messages.requestUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getTopPeers.html" name="contacts.getTopPeers">Get most used peers: contacts.getTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getDifference.html" name="langpack.getDifference">Get new strings in languagepack: langpack.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentReceipt.html" name="payments.getPaymentReceipt">Get payment receipt: payments.getPaymentReceipt</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerSettings.html" name="messages.getPeerSettings">Get peer settings: messages.getPeerSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getCallConfig.html" name="phone.getCallConfig">Get phone call configuration to be passed to libtgvoip's shared config: phone.getCallConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPinnedDialogs.html" name="messages.getPinnedDialogs">Get pinned dialogs: messages.getPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPollResults.html" name="messages.getPollResults">Get poll results: messages.getPollResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPagePreview.html" name="messages.getWebPagePreview">Get preview of webpage: messages.getWebPagePreview</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPrivacy.html" name="account.getPrivacy">Get privacy settings of current account: account.getPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getProxyData.html" name="help.getProxyData">Get promotion info of the currently-used MTProxy: help.getProxyData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentStickers.html" name="messages.getRecentStickers">Get recent stickers: messages.getRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getRecentMeUrls.html" name="help.getRecentMeUrls">Get recently used t.me links: help.getRecentMeUrls</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSavedGifs.html" name="messages.getSavedGifs">Get saved GIFs: messages.getSavedGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getSecureValue.html" name="account.getSecureValue">Get saved Telegram Passport document, for more info see the passport docs »: account.getSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getSavedInfo.html" name="payments.getSavedInfo">Get saved payment information: payments.getSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledHistory.html" name="messages.getScheduledHistory">Get scheduled messages: messages.getScheduledHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledMessages.html" name="messages.getScheduledMessages">Get scheduled messages: messages.getScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAttachedStickers.html" name="messages.getAttachedStickers">Get stickers attached to a photo or video: messages.getAttachedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickers.html" name="messages.getStickers">Get stickers by emoji: messages.getStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getStrings.html" name="langpack.getStrings">Get strings from a language pack: langpack.getStrings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTmpPassword.html" name="account.getTmpPassword">Get temporary payment password: account.getTmpPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminLog.html" name="channels.getAdminLog">Get the admin log of a channel/supergroup: channels.getAdminLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSearchCounters.html" name="messages.getSearchCounters">Get the number of results that would be found by a messages.search call with the same parameters: messages.getSearchCounters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipants.html" name="channels.getParticipants">Get the participants of a channel: channels.getParticipants</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTheme.html" name="account.getTheme">Get theme information: account.getTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getUnreadMentions.html" name="messages.getUnreadMentions">Get unread messages where we were mentioned: messages.getUnreadMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWebAuthorizations.html" name="account.getWebAuthorizations">Get web login widget authorizations: account.getWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.search.html" name="messages.search">Gets back found messages: messages.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getHistory.html" name="messages.getHistory">Gets back the conversation history with one interlocutor / within a chat: messages.getHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifySettings.html" name="account.getNotifySettings">Gets current notification settings for a given user/group, from all users/all groups: account.getNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.togglePreHistoryHidden.html" name="channels.togglePreHistoryHidden">Hide/unhide message history for new channel/supergroup users: channels.togglePreHistoryHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.acceptContact.html" name="contacts.acceptContact">If the peer settings of a new user allow us to add him as contact, add that user as contact: contacts.acceptContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotShippingResults.html" name="messages.setBotShippingResults">If you sent an invoice requesting a shipping address and the parameter is_flexible was specified, the bot will receive an updateBotShippingQuery update. Use this method to reply to shipping queries: messages.setBotShippingResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.importChatInvite.html" name="messages.importChatInvite">Import a chat invite and join a private chat/supergroup/channel: messages.importChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.importContacts.html" name="contacts.importContacts">Imports contacts: saves a full list on the server, adds already registered contacts to the contact list, returns added contacts and their info: contacts.importContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.setBotUpdatesStatus.html" name="help.setBotUpdatesStatus">Informs the server about the number of pending bot updates if they haven't been processed for a long time; for bots only: help.setBotUpdatesStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/initConnection.html" name="initConnection">Initialize connection: initConnection</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.installStickerSet.html" name="messages.installStickerSet">Install a stickerset: messages.installStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installTheme.html" name="account.installTheme">Install a theme: account.installTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installWallPaper.html" name="account.installWallPaper">Install wallpaper: account.installWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveWallPaper.html" name="account.saveWallPaper">Install/uninstall wallpaper: account.saveWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.updateProfilePhoto.html" name="photos.updateProfilePhoto">Installs a previously uploaded photo as a profile photo: photos.updateProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.editUserInfo.html" name="help.editUserInfo">Internal use: help.editUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getUserInfo.html" name="help.getUserInfo">Internal use: help.getUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.initTakeoutSession.html" name="account.initTakeoutSession">Intialize account takeout session: account.initTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.inviteToChannel.html" name="channels.inviteToChannel">Invite users to a channel/supergroup: channels.inviteToChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithTakeout.html" name="invokeWithTakeout">Invoke a method within a takeout session: invokeWithTakeout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithoutUpdates.html" name="invokeWithoutUpdates">Invoke a request without subscribing the used connection for updates (this is enabled by default for file queries): invokeWithoutUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithLayer.html" name="invokeWithLayer">Invoke the specified query using the specified API layer: invokeWithLayer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithMessagesRange.html" name="invokeWithMessagesRange">Invoke with the given message range: invokeWithMessagesRange</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsgs.html" name="invokeAfterMsgs">Invokes a query after a successfull completion of previous queries: invokeAfterMsgs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsg.html" name="invokeAfterMsg">Invokes a query after successfull completion of one of the previous queries: invokeAfterMsg</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.joinChannel.html" name="channels.joinChannel">Join a channel/supergroup: channels.joinChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.leaveChannel.html" name="channels.leaveChannel">Leave a channel/supergroup: channels.leaveChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetAuthorization.html" name="account.resetAuthorization">Log out an active authorized session by its hash: account.resetAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorization.html" name="account.resetWebAuthorization">Log out an active web telegram login session: account.resetWebAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getTermsOfServiceUpdate.html" name="help.getTermsOfServiceUpdate">Look for updates of telegram's terms of service: help.getTermsOfServiceUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAdmin.html" name="messages.editChatAdmin">Make a user admin in a legacy group: messages.editChatAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.markDialogUnread.html" name="messages.markDialogUnread">Manually mark dialog as unread: messages.markDialogUnread</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readHistory.html" name="channels.readHistory">Mark channel/supergroup history as read: channels.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readMessageContents.html" name="channels.readMessageContents">Mark channel/supergroup message contents as read: channels.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.faveSticker.html" name="messages.faveSticker">Mark a sticker as favorite: messages.faveSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMentions.html" name="messages.readMentions">Mark mentions as read: messages.readMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readFeaturedStickers.html" name="messages.readFeaturedStickers">Mark new featured stickers as read: messages.readFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readHistory.html" name="messages.readHistory">Marks message history as read: messages.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readEncryptedHistory.html" name="messages.readEncryptedHistory">Marks message history within a secret chat as read: messages.readEncryptedHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editAdmin.html" name="channels.editAdmin">Modify the admin rights of a user in a supergroup/channel: channels.editAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMessageContents.html" name="messages.readMessageContents">Notifies the sender about the recipient having listened a voice message or watched a video: messages.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScreenshotNotification.html" name="messages.sendScreenshotNotification">Notify the other user in a private chat that a screenshot of the chat was taken: messages.sendScreenshotNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.setSecureValueErrors.html" name="users.setSecureValueErrors">Notify the user that the sent passport data contains some errors The user will not be able to re-submit their Passport data to you until the errors are fixed (the contents of the field for which you returned the error must change): users.setSecureValueErrors</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPassword.html" name="account.getPassword">Obtain configuration for two-factor authorization with password: account.getPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotPrecheckoutResults.html" name="messages.setBotPrecheckoutResults">Once the user has confirmed their payment and shipping details, the bot receives an updateBotPrecheckoutQuery update.  : messages.setBotPrecheckoutResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.receivedCall.html" name="phone.receivedCall">Optional: notify the server that the user is currently busy in a call: this will automatically refuse all incoming phone calls until the current phone call is ended: phone.receivedCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.updatePinnedMessage.html" name="messages.updatePinnedMessage">Pin a message: messages.updatePinnedMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleDialogPin.html" name="messages.toggleDialogPin">Pin/unpin a dialog: messages.toggleDialogPin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getBotCallbackAnswer.html" name="messages.getBotCallbackAnswer">Press an inline callback button and get a callback answer from the bot: messages.getBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineBotResults.html" name="messages.getInlineBotResults">Query an inline bot: messages.getInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.setCallRating.html" name="phone.setCallRating">Rate a call: phone.setCallRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.registerDevice.html" name="account.registerDevice">Register device to receive PUSH notifications: account.registerDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.removeStickerFromSet.html" name="stickers.removeStickerFromSet">Remove a sticker from the set where it belongs, bots only. The sticker set must have been created by the bot: stickers.removeStickerFromSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderStickerSets.html" name="messages.reorderStickerSets">Reorder installed stickersets: messages.reorderStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderPinnedDialogs.html" name="messages.reorderPinnedDialogs">Reorder pinned dialogs: messages.reorderPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.report.html" name="messages.report">Report a message in a chat for violation of telegram's Terms of Service: messages.report</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportSpam.html" name="messages.reportSpam">Report a new incoming chat for spam, if the peer settings of the chat allow us to do that: messages.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.reportPeer.html" name="account.reportPeer">Report a peer for violation of telegram's Terms of Service: account.reportPeer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportEncryptedSpam.html" name="messages.reportEncryptedSpam">Report a secret chat for spam: messages.reportEncryptedSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reportSpam.html" name="channels.reportSpam">Reports some messages from a user in a supergroup as spam; requires administrator rights in the supergroup: channels.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.requestPasswordRecovery.html" name="auth.requestPasswordRecovery">Request recovery code of a 2FA password, only for accounts with a recovery email configured: auth.requestPasswordRecovery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resendPasswordEmail.html" name="account.resendPasswordEmail">Resend the code to verify an email to use as 2FA recovery method: account.resendPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resendCode.html" name="auth.resendCode">Resend the login code via another medium, the phone code type is determined by the return value of the previous auth.sendCode/auth.resendCode: see login for more info: auth.resendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetTopPeerRating.html" name="contacts.resetTopPeerRating">Reset rating of top peer: contacts.resetTopPeerRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorizations.html" name="account.resetWebAuthorizations">Reset all active web telegram login sessions: account.resetWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.recoverPassword.html" name="auth.recoverPassword">Reset the 2FA password using the recovery code sent using auth.requestPasswordRecovery: auth.recoverPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetNotifySettings.html" name="account.resetNotifySettings">Resets all notification settings from users and groups: account.resetNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStatsURL.html" name="messages.getStatsURL">Returns URL with the chat statistics. Currently this method can be used only for channels: messages.getStatsURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizationForm.html" name="account.getAuthorizationForm">Returns a Telegram Passport authorization form for sharing data with a service: account.getAuthorizationForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPapers.html" name="account.getWallPapers">Returns a list of available wallpapers: account.getWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiURL.html" name="messages.getEmojiURL">Returns an HTTP URL which can be used to automatically log in into translation platform and suggest new emoji replacements. The URL will be valid for 30 seconds after generation: messages.getEmojiURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.getUsers.html" name="users.getUsers">Returns basic user info according to their identifiers: users.getUsers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getChats.html" name="messages.getChats">Returns chat basic info on their IDs: messages.getChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getWebFile.html" name="upload.getWebFile">Returns content of an HTTP file or a part, by proxying the request through telegram: upload.getWebFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getConfig.html" name="help.getConfig">Returns current configuration, icluding data center configuration: help.getConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getNearestDc.html" name="help.getNearestDc">Returns info on data centre nearest to the user: help.getNearestDc</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppUpdate.html" name="help.getAppUpdate">Returns information on update availability for the current application: help.getAppUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifyExceptions.html" name="account.getNotifyExceptions">Returns list of chats with non-default notification settings: account.getNotifyExceptions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getInviteText.html" name="help.getInviteText">Returns text of a text message with an invitation: help.getInviteText</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogs.html" name="messages.getDialogs">Returns the current user dialog list: messages.getDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContacts.html" name="contacts.getContacts">Returns the current user's contact list: contacts.getContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getBlocked.html" name="contacts.getBlocked">Returns the list of blocked users: contacts.getBlocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getStatuses.html" name="contacts.getStatuses">Returns the list of contact statuses: contacts.getStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessages.html" name="messages.getMessages">Returns the list of messages by their IDs: messages.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.getUserPhotos.html" name="photos.getUserPhotos">Returns the list of user photos: photos.getUserPhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupport.html" name="help.getSupport">Returns the support user for the 'ask a question' feature: help.getSupport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.search.html" name="contacts.search">Returns users found by username substring: contacts.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveDraft.html" name="messages.saveDraft">Save a message draft associated to a chat: messages.saveDraft</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveTheme.html" name="account.saveTheme">Save a theme: account.saveTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllDrafts.html" name="messages.getAllDrafts">Save get all message drafts: messages.getAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.saveAppLog.html" name="help.saveAppLog">Saves logs of application on the server: help.saveAppLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchGifs.html" name="messages.searchGifs">Search for GIFs: messages.searchGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchGlobal.html" name="messages.searchGlobal">Search for messages and peers globally: messages.searchGlobal</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchStickerSets.html" name="messages.searchStickerSets">Search for stickersets: messages.searchStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveSecureValue.html" name="account.saveSecureValue">Securely save Telegram Passport document, for more info see the passport docs »: account.saveSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMedia.html" name="messages.sendMedia">Send a media: messages.sendMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendInlineBotResult.html" name="messages.sendInlineBotResult">Send a result obtained using messages.getInlineBotResults: messages.sendInlineBotResult</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMultiMedia.html" name="messages.sendMultiMedia">Send an album of media: messages.sendMultiMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.sendPaymentForm.html" name="payments.sendPaymentForm">Send compiled payment form: payments.sendPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendConfirmPhoneCode.html" name="account.sendConfirmPhoneCode">Send confirmation code to cancel account deletion, for more info click here »: account.sendConfirmPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.saveCallDebug.html" name="phone.saveCallDebug">Send phone call debug data to server: phone.saveCallDebug</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScheduledMessages.html" name="messages.sendScheduledMessages">Send scheduled messages right away: messages.sendScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyEmailCode.html" name="account.sendVerifyEmailCode">Send the verification email code for telegram passport: account.sendVerifyEmailCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyPhoneCode.html" name="account.sendVerifyPhoneCode">Send the verification phone code for telegram passport: account.sendVerifyPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setEncryptedTyping.html" name="messages.setEncryptedTyping">Send typing event by the current user to a secret chat: messages.setEncryptedTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.acceptAuthorization.html" name="account.acceptAuthorization">Sends a Telegram Passport authorization form, effectively sharing data with the service: account.acceptAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setTyping.html" name="messages.setTyping">Sends a current user typing event (see SendMessageAction for all event types) to a conversation partner or group: messages.setTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.sendCustomRequest.html" name="bots.sendCustomRequest">Sends a custom request; for bots only: bots.sendCustomRequest</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMessage.html" name="messages.sendMessage">Sends a message to a chat: messages.sendMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedFile.html" name="messages.sendEncryptedFile">Sends a message with a file attachment to a secret chat: messages.sendEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedService.html" name="messages.sendEncryptedService">Sends a service message to a secret chat: messages.sendEncryptedService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncrypted.html" name="messages.sendEncrypted">Sends a text message to a secret chat: messages.sendEncrypted</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setAccountTTL.html" name="account.setAccountTTL">Set account self-destruction period: account.setAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotCallbackAnswer.html" name="messages.setBotCallbackAnswer">Set the callback answer to a user button press (bots only): messages.setBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.hidePeerSettingsBar.html" name="messages.hidePeerSettingsBar">Should be called after the user hides the report spam/add as contact bar of a new chat, effectively prevents the user from executing the actions specified in the peer's settings: messages.hidePeerSettingsBar</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.startBot.html" name="messages.startBot">Start a conversation with a bot using a deep linking parameter: messages.startBot</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.validateRequestedInfo.html" name="payments.validateRequestedInfo">Submit requested order information for validation: payments.validateRequestedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resetAuthorizations.html" name="auth.resetAuthorizations">Terminates all user's authorized sessions except for the current one: auth.resetAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setContactSignUpNotification.html" name="account.setContactSignUpNotification">Toggle contact sign up notifications: account.setContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSlowMode.html" name="channels.toggleSlowMode">Toggle supergroup slow mode: if enabled, users will only be able to send one message every seconds seconds: channels.toggleSlowMode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editCreator.html" name="channels.editCreator">Transfer channel ownership: channels.editCreator</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.migrateChat.html" name="messages.migrateChat">Turn a legacy group into a supergroup: messages.migrateChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uninstallStickerSet.html" name="messages.uninstallStickerSet">Uninstall a stickerset: messages.uninstallStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateTheme.html" name="account.updateTheme">Update theme: account.updateTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.uploadProfilePhoto.html" name="photos.uploadProfilePhoto">Updates current user profile photo: photos.uploadProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateStatus.html" name="account.updateStatus">Updates online user status: account.updateStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateProfile.html" name="account.updateProfile">Updates user profile: account.updateProfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadMedia.html" name="messages.uploadMedia">Upload a file and associate it to a chat (without actually sending it to the chat): messages.uploadMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadEncryptedFile.html" name="messages.uploadEncryptedFile">Upload encrypted file and associate it to a secret chat: messages.uploadEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadTheme.html" name="account.uploadTheme">Upload theme: account.uploadTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setGameScore.html" name="messages.setGameScore">Use this method to set the score of the specified user in a game sent as a normal message (bots only): messages.setGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineGameScore.html" name="messages.setInlineGameScore">Use this method to set the score of the specified user in a game sent as an inline message (bots only): messages.setInlineGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.acceptUrlAuth.html" name="messages.acceptUrlAuth">Use this to accept a Seamless Telegram Login authorization request, for more info click here »: messages.acceptUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.checkUsername.html" name="account.checkUsername">Validates a username and checks availability: account.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendChangePhoneCode.html" name="account.sendChangePhoneCode">Verify a new phone number to associate to the current account: account.sendChangePhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyPhone.html" name="account.verifyPhone">Verify a phone number for telegram passport: account.verifyPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyEmail.html" name="account.verifyEmail">Verify an email address for telegram passport: account.verifyEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPasswordEmail.html" name="account.confirmPasswordEmail">Verify an email to use as 2FA recovery method: account.confirmPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendVote.html" name="messages.sendVote">Vote in a poll: messages.sendVote</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateDeviceLocked.html" name="account.updateDeviceLocked">When client-side passcode lock feature is enabled, will not show message texts in incoming PUSH notifications: account.updateDeviceLocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getContactSignUpNotification.html" name="account.getContactSignUpNotification">Whether the user will receive notifications when contacts sign up: account.getContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.bindTempAuthKey.html" name="auth.bindTempAuthKey">You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info: auth.bindTempAuthKey</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDhConfig.html" name="messages.getDhConfig">You cannot use this method directly, instead use $MadelineProto->getDhConfig();: messages.getDhConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.acceptEncryption.html" name="messages.acceptEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.acceptEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.discardEncryption.html" name="messages.discardEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.discardEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestEncryption.html" name="messages.requestEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.requestEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates.getChannelDifference.html" name="updates.getChannelDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getChannelDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates.getDifference.html" name="updates.getDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates.getState.html" name="updates.getState">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getState</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.acceptCall.html" name="phone.acceptCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.acceptCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.confirmCall.html" name="phone.confirmCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.confirmCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.discardCall.html" name="phone.discardCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.discardCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.requestCall.html" name="phone.requestCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.requestCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.exportAuthorization.html" name="auth.exportAuthorization">You cannot use this method directly, use $MadelineProto->exportAuthorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html: auth.exportAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.importAuthorization.html" name="auth.importAuthorization">You cannot use this method directly, use $MadelineProto->importAuthorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html: auth.importAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.importBotAuthorization.html" name="auth.importBotAuthorization">You cannot use this method directly, use the botLogin method instead (see https://docs.madelineproto.xyz for more info): auth.importBotAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.signIn.html" name="auth.signIn">You cannot use this method directly, use the completePhoneLogin method instead (see https://docs.madelineproto.xyz for more info): auth.signIn</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.signUp.html" name="auth.signUp">You cannot use this method directly, use the completeSignup method instead (see https://docs.madelineproto.xyz for more info): auth.signUp</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.checkPassword.html" name="auth.checkPassword">You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info): auth.checkPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getFullChannel.html" name="channels.getFullChannel">You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info): channels.getFullChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFullChat.html" name="messages.getFullChat">You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info): messages.getFullChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.getFullUser.html" name="users.getFullUser">You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info): users.getFullUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.logOut.html" name="auth.logOut">You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info): auth.logOut</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.sendCode.html" name="auth.sendCode">You cannot use this method directly, use the phoneLogin method instead (see https://docs.madelineproto.xyz for more info): auth.sendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resolveUsername.html" name="contacts.resolveUsername">You cannot use this method directly, use the resolveUsername, getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info): contacts.resolveUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getCdnFile.html" name="upload.getCdnFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getCdnFileHashes.html" name="upload.getCdnFileHashes">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getFile.html" name="upload.getFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getFileHashes.html" name="upload.getFileHashes">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.reuploadCdnFile.html" name="upload.reuploadCdnFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.reuploadCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.saveBigFilePart.html" name="upload.saveBigFilePart">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveBigFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.saveFilePart.html" name="upload.saveFilePart">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.receivedQueue.html" name="messages.receivedQueue">You cannot use this method directly: messages.receivedQueue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPasswordSettings.html" name="account.getPasswordSettings">You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info): account.getPasswordSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updatePasswordSettings.html" name="account.updatePasswordSettings">You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info): account.updatePasswordSettings</a>
  * [Peers](https://docs.madelineproto.xyz/docs/USING_METHODS.html#peers)
  * [Files](https://docs.madelineproto.xyz/docs/FILES.html)
  * [Secret chats](https://docs.madelineproto.xyz/docs/USING_METHODS.html#secret-chats)
  * [Entities (Markdown & HTML)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#entities)
  * [reply_markup (keyboards & inline keyboards)](https://docs.madelineproto.xyz/docs/USING_METHODS.html#reply_markup)
  * [bot API objects](https://docs.madelineproto.xyz/docs/USING_METHODS.html#bot-api-objects)
  * [No result](https://docs.madelineproto.xyz/docs/USING_METHODS.html#no-result)
  * [Queues](https://docs.madelineproto.xyz/docs/USING_METHODS.html#queues)
  * [Multiple method calls](https://docs.madelineproto.xyz/docs/USING_METHODS.html#multiple-method-calls)
* [Contributing](https://docs.madelineproto.xyz/docs/CONTRIB.html)
  * [Translation](https://docs.madelineproto.xyz/docs/CONTRIB.html#translation)
  * [Contribution guide](https://docs.madelineproto.xyz/docs/CONTRIB.html#contribution-guide)
  * [Credits](https://docs.madelineproto.xyz/docs/CONTRIB.html#credits)
* [Web templates for `$MadelineProto->start()`](https://docs.madelineproto.xyz/docs/TEMPLATES.html)

## Very complex and complete examples

You can find examples for nearly every MadelineProto function in
* [magnaluna webradio](https://magna.madelineproto.xyz) - Multifeatured Telegram VoIP webradio
* [`downloadRenameBot.php`](https://github.com/danog/MadelineProto/blob/master/examples/downloadRenameBot.php) - download files by URL and rename Telegram files using this async parallelized bot!
* [`tests/testing.php`](https://github.com/danog/MadelineProto/blob/master/tests/testing.php) - examples for making/receiving calls, making secret chats, sending secret chat messages, videos, audios, voice recordings, gifs, stickers, photos, sending normal messages, videos, audios, voice recordings, gifs, stickers, photos.
* [`bot.php`](https://github.com/danog/MadelineProto/blob/master/examples/bot.php) - examples for sending normal messages, downloading any media
* [`secret_bot.php`](https://github.com/danog/MadelineProto/blob/master/examples/secret_bot.php) - secret chat bot
* [`pipesbot.php`](https://github.com/danog/MadelineProto/blob/master/examples/pipesbot.php) - examples for creating inline bots and using other inline bots via a userbot


