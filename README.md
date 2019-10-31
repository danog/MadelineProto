# MadelineProto, a PHP MTProto telegram client

Created by <a href="https://daniil.it" target="_blank" rel="noopener">Daniil Gentili</a>

Do join the official channel, [@MadelineProto](https://t.me/MadelineProto) and the [support groups](https://t.me/pwrtelegramgroup)!

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
  * [Bot API file IDs](https://docs.madelineproto.xyz/docs/FILES.html#bot-api-file-ids)
  * [Reusing uploaded files](https://docs.madelineproto.xyz/docs/FILES.html#reusing-uploaded-files)
  * [Downloading files](https://docs.madelineproto.xyz/docs/FILES.html#downloading-files)
    * [Extracting download info](https://docs.madelineproto.xyz/docs/FILES.html#extracting-download-info)
    * [Downloading profile pictures](https://docs.madelineproto.xyz/docs/FILES.html#downloading-profile-pictures)
    * [Download to directory](https://docs.madelineproto.xyz/docs/FILES.html#download-to-directory)
    * [Download to file](https://docs.madelineproto.xyz/docs/FILES.html#download-to-file)
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
* [Using methods](https://docs.madelineproto.xyz/docs/USING_METHODS.html)
  * [FULL API Documentation with descriptions](https://docs.madelineproto.xyz/API_docs/methods/)
    * [Logout](https://docs.madelineproto.xyz/logout.html)
    * [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)
    * [Change 2FA password](https://docs.madelineproto.xyz/update_2fa.html)
    * [Get all chats, broadcast a message to all chats](https://docs.madelineproto.xyz/docs/DIALOGS.html)
    * [Get the full participant list of a channel/group/supergroup](https://docs.madelineproto.xyz/getPwrChat.html)
    * [Get full info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/getFullInfo.html)
    * [Get info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/getInfo.html)
    * [Get info about the currently logged-in user](https://docs.madelineproto.xyz/getSelf.html)
    * [Upload or download files up to 1.5 GB](https://docs.madelineproto.xyz/docs/FILES.html)
    * [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)
    * [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.acceptUrlAuth.html" name="messages.acceptUrlAuth">Accept URL authorization: messages.acceptUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.acceptContact.html" name="contacts.acceptContact">Accept contact: contacts.acceptContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.acceptAuthorization.html" name="account.acceptAuthorization">Accept telegram passport authorization: account.acceptAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.acceptTermsOfService.html" name="help.acceptTermsOfService">Accept telegram's TOS: help.acceptTermsOfService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.installStickerSet.html" name="messages.installStickerSet">Add a sticker set: messages.installStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.faveSticker.html" name="messages.faveSticker">Add a sticker to favorites: messages.faveSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveRecentSticker.html" name="messages.saveRecentSticker">Add a sticker to recent stickers: messages.saveRecentSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.addChatUser.html" name="messages.addChatUser">Add a user to a normal chat (use channels->inviteToChannel for supergroups): messages.addChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.addContact.html" name="contacts.addContact">Add contact: contacts.addContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.importContacts.html" name="contacts.importContacts">Add phone number as contact: contacts.importContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.addStickerToSet.html" name="stickers.addStickerToSet">Add sticker to stickerset: stickers.addStickerToSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.inviteToChannel.html" name="channels.inviteToChannel">Add users to channel/supergroup: channels.inviteToChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.block.html" name="contacts.block">Block a user: contacts.block</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizationForm.html" name="account.getAuthorizationForm">Bots only: get telegram passport authorization form: account.getAuthorizationForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.sendPaymentForm.html" name="payments.sendPaymentForm">Bots only: send payment form: payments.sendPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotPrecheckoutResults.html" name="messages.setBotPrecheckoutResults">Bots only: set precheckout results: messages.setBotPrecheckoutResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotShippingResults.html" name="messages.setBotShippingResults">Bots only: set shipping results: messages.setBotShippingResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setBotCallbackAnswer.html" name="messages.setBotCallbackAnswer">Bots only: set the callback answer (after a button was clicked): messages.setBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineBotResults.html" name="messages.setInlineBotResults">Bots only: set the results of an inline query: messages.setInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineBotResults.html" name="messages.getInlineBotResults">Call inline bot: messages.getInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.cancelPasswordEmail.html" name="account.cancelPasswordEmail">Cancel password recovery email: account.cancelPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateNotifySettings.html" name="account.updateNotifySettings">Change notification settings: account.updateNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.changeStickerPosition.html" name="stickers.changeStickerPosition">Change sticker position in photo: stickers.changeStickerPosition</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.changePhone.html" name="account.changePhone">Change the phone number associated to this account: account.changePhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendChangePhoneCode.html" name="account.sendChangePhoneCode">Change the phone number: account.sendChangePhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.updateProfilePhoto.html" name="photos.updateProfilePhoto">Change the profile photo: photos.updateProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setTyping.html" name="messages.setTyping">Change typing status: messages.setTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessageEditData.html" name="messages.getMessageEditData">Check if about to edit a message or a media caption: messages.getMessageEditData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.checkChatInvite.html" name="messages.checkChatInvite">Check if an invitation link is valid: messages.checkChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.checkUsername.html" name="account.checkUsername">Check if this username is available: account.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.checkUsername.html" name="channels.checkUsername">Check if this username is free and can be assigned to a channel/supergroup: channels.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearAllDrafts.html" name="messages.clearAllDrafts">Clear all drafts: messages.clearAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.clearRecentStickers.html" name="messages.clearRecentStickers">Clear all recent stickers: messages.clearRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.clearSavedInfo.html" name="payments.clearSavedInfo">Clear saved payments info: payments.clearSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPasswordEmail.html" name="account.confirmPasswordEmail">Confirm password recovery using email: account.confirmPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.confirmPhone.html" name="account.confirmPhone">Confirm this phone number is associated to this account, obtain phone_code_hash from sendConfirmPhoneCode: account.confirmPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getContactSignUpNotification.html" name="account.getContactSignUpNotification">Contact signup notification setting value: account.getContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.migrateChat.html" name="messages.migrateChat">Convert chat to supergroup: messages.migrateChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.createChat.html" name="messages.createChat">Create a chat (not supergroup): messages.createChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.createTheme.html" name="account.createTheme">Create a theme: account.createTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.createChannel.html" name="channels.createChannel">Create channel/supergroup: channels.createChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.createStickerSet.html" name="stickers.createStickerSet">Create stickerset: stickers.createStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetAuthorization.html" name="account.resetAuthorization">Delete a certain session: account.resetAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorization.html" name="account.resetWebAuthorization">Delete a certain telegram web login authorization: account.resetWebAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteChannel.html" name="channels.deleteChannel">Delete a channel/supergroup: channels.deleteChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteChatUser.html" name="messages.deleteChatUser">Delete a user from a chat (not supergroup): messages.deleteChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resetAuthorizations.html" name="auth.resetAuthorizations">Delete all logged-in sessions.: auth.resetAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteUserHistory.html" name="channels.deleteUserHistory">Delete all messages of a user in a channel/supergroup: channels.deleteUserHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.dropTempAuthKeys.html" name="auth.dropTempAuthKeys">Delete all temporary authorization keys except the ones provided: auth.dropTempAuthKeys</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteMessages.html" name="channels.deleteMessages">Delete channel/supergroup messages: channels.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteHistory.html" name="messages.deleteHistory">Delete chat history: messages.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteByPhones.html" name="contacts.deleteByPhones">Delete contacts by phones: contacts.deleteByPhones</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders.deleteFolder.html" name="folders.deleteFolder">Delete folder: folders.deleteFolder</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteMessages.html" name="messages.deleteMessages">Delete messages: messages.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.deleteContacts.html" name="contacts.deleteContacts">Delete multiple contacts: contacts.deleteContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.deletePhotos.html" name="photos.deletePhotos">Delete profile photos: photos.deletePhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.deleteScheduledMessages.html" name="messages.deleteScheduledMessages">Delete scheduled messages: messages.deleteScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteSecureValue.html" name="account.deleteSecureValue">Delete secure telegram passport value: account.deleteSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.deleteHistory.html" name="channels.deleteHistory">Delete the history of a supergroup/channel: channels.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.deleteAccount.html" name="account.deleteAccount">Delete this account: account.deleteAccount</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateDeviceLocked.html" name="account.updateDeviceLocked">Disable all notifications for a certain period: account.updateDeviceLocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getWebFile.html" name="upload.getWebFile">Download a file through telegram: upload.getWebFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editMessage.html" name="messages.editMessage">Edit a message: messages.editMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editInlineBotMessage.html" name="messages.editInlineBotMessage">Edit a sent inline message: messages.editInlineBotMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editAdmin.html" name="channels.editAdmin">Edit admin permissions of a user in a channel/supergroup: channels.editAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAdmin.html" name="messages.editChatAdmin">Edit admin permissions: messages.editChatAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatAbout.html" name="messages.editChatAbout">Edit chat info: messages.editChatAbout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editCreator.html" name="channels.editCreator">Edit creator of channel: channels.editCreator</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatDefaultBannedRights.html" name="messages.editChatDefaultBannedRights">Edit default rights of chat: messages.editChatDefaultBannedRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders.editPeerFolders.html" name="folders.editPeerFolders">Edit folder: folders.editPeerFolders</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editLocation.html" name="channels.editLocation">Edit location (geochats): channels.editLocation</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatPhoto.html" name="messages.editChatPhoto">Edit the photo of a normal chat (not supergroup): messages.editChatPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editPhoto.html" name="channels.editPhoto">Edit the photo of a supergroup/channel: channels.editPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.editChatTitle.html" name="messages.editChatTitle">Edit the title of a normal chat (not supergroup): messages.editChatTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editTitle.html" name="channels.editTitle">Edit the title of a supergroup/channel: channels.editTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.editUserInfo.html" name="help.editUserInfo">Edit user info: help.editUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.togglePreHistoryHidden.html" name="channels.togglePreHistoryHidden">Enable or disable hidden history for new channel/supergroup users: channels.togglePreHistoryHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.exportChatInvite.html" name="messages.exportChatInvite">Export chat invite : messages.exportChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchStickerSets.html" name="messages.searchStickerSets">Find a sticker set: messages.searchStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.finishTakeoutSession.html" name="account.finishTakeoutSession">Finish account exporting session: account.finishTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.forwardMessages.html" name="messages.forwardMessages">Forward messages: messages.forwardMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getCdnConfig.html" name="help.getCdnConfig">Get CDN configuration: help.getCdnConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickerSet.html" name="messages.getStickerSet">Get a stickerset: messages.getStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAccountTTL.html" name="account.getAccountTTL">Get account TTL: account.getAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminLog.html" name="channels.getAdminLog">Get admin log of a channel/supergroup: channels.getAdminLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getArchivedStickers.html" name="messages.getArchivedStickers">Get all archived stickers: messages.getArchivedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getLeftChannels.html" name="channels.getLeftChannels">Get all channels you left: channels.getLeftChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllChats.html" name="messages.getAllChats">Get all chats (not supergroups or channels): messages.getAllChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContacts.html" name="contacts.getContacts">Get all contacts: contacts.getContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAuthorizations.html" name="account.getAuthorizations">Get all logged-in authorizations: account.getAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllDrafts.html" name="messages.getAllDrafts">Get all message drafts: messages.getAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAllSecureValues.html" name="account.getAllSecureValues">Get all secure telegram passport values: account.getAllSecureValues</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAllStickers.html" name="messages.getAllStickers">Get all stickerpacks: messages.getAllStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getAdminedPublicChannels.html" name="channels.getAdminedPublicChannels">Get all supergroups/channels where you're admin: channels.getAdminedPublicChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessagesViews.html" name="messages.getMessagesViews">Get and increase message views: messages.getMessagesViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppConfig.html" name="help.getAppConfig">Get app config: help.getAppConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getAutoDownloadSettings.html" name="account.getAutoDownloadSettings">Get autodownload settings: account.getAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguages.html" name="langpack.getLanguages">Get available languages: langpack.getLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getBlocked.html" name="contacts.getBlocked">Get blocked users: contacts.getBlocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.getCallConfig.html" name="phone.getCallConfig">Get call configuration: phone.getCallConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getMessages.html" name="channels.getMessages">Get channel/supergroup messages: channels.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipants.html" name="channels.getParticipants">Get channel/supergroup participants (you should use `$MadelineProto->getPwrChat($id)` instead): channels.getParticipants</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getCommonChats.html" name="messages.getCommonChats">Get chats in common with a user: messages.getCommonChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getContactIDs.html" name="contacts.getContactIDs">Get contacts by IDs: contacts.getContactIDs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getDeepLinkInfo.html" name="help.getDeepLinkInfo">Get deep link info: help.getDeepLinkInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerDialogs.html" name="messages.getPeerDialogs">Get dialog info of peers: messages.getPeerDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogUnreadMarks.html" name="messages.getDialogUnreadMarks">Get dialogs marked as unread manually: messages.getDialogUnreadMarks</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDocumentByHash.html" name="messages.getDocumentByHash">Get document by SHA256 hash: messages.getDocumentByHash</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiURL.html" name="messages.getEmojiURL">Get emoji URL: messages.getEmojiURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsDifference.html" name="messages.getEmojiKeywordsDifference">Get emoji keyword difference: messages.getEmojiKeywordsDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywordsLanguages.html" name="messages.getEmojiKeywordsLanguages">Get emoji keyword languages: messages.getEmojiKeywordsLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getEmojiKeywords.html" name="messages.getEmojiKeywords">Get emoji keywords: messages.getEmojiKeywords</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFavedStickers.html" name="messages.getFavedStickers">Get favorite stickers: messages.getFavedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getFeaturedStickers.html" name="messages.getFeaturedStickers">Get featured stickers: messages.getFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getFileHashes.html" name="upload.getFileHashes">Get file hashes: upload.getFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getGroupsForDiscussion.html" name="channels.getGroupsForDiscussion">Get groups for discussion: channels.getGroupsForDiscussion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getInlineGameHighScores.html" name="messages.getInlineGameHighScores">Get high scores of a game sent in an inline message: messages.getInlineGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getGameHighScores.html" name="messages.getGameHighScores">Get high scores of a game: messages.getGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getParticipant.html" name="channels.getParticipant">Get info about a certain channel/supergroup participant: channels.getParticipant</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppUpdate.html" name="help.getAppUpdate">Get info about app updates: help.getAppUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getChats.html" name="messages.getChats">Get info about chats: messages.getChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.getChannels.html" name="channels.getChannels">Get info about multiple channels/supergroups: channels.getChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.getUsers.html" name="users.getUsers">Get info about users: users.getUsers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupport.html" name="help.getSupport">Get info of support user: help.getSupport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getProxyData.html" name="help.getProxyData">Get information about the current proxy: help.getProxyData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getThemes.html" name="account.getThemes">Get installed themes: account.getThemes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getInviteText.html" name="help.getInviteText">Get invitation text: help.getInviteText</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getStrings.html" name="langpack.getStrings">Get language pack strings: langpack.getStrings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getDifference.html" name="langpack.getDifference">Get language pack updates: langpack.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLangPack.html" name="langpack.getLangPack">Get language pack: langpack.getLangPack</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack.getLanguage.html" name="langpack.getLanguage">Get language: langpack.getLanguage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMaskStickers.html" name="messages.getMaskStickers">Get masks: messages.getMaskStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSplitRanges.html" name="messages.getSplitRanges">Get message ranges to fetch: messages.getSplitRanges</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getMessages.html" name="messages.getMessages">Get messages: messages.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getTopPeers.html" name="contacts.getTopPeers">Get most used chats: contacts.getTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getNearestDc.html" name="help.getNearestDc">Get nearest datacenter: help.getNearestDc</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifyExceptions.html" name="account.getNotifyExceptions">Get notification exceptions: account.getNotifyExceptions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getNotifySettings.html" name="account.getNotifySettings">Get notification settings: account.getNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getStatuses.html" name="contacts.getStatuses">Get online status of all users: contacts.getStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getOnlines.html" name="messages.getOnlines">Get online users: messages.getOnlines</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getPassportConfig.html" name="help.getPassportConfig">Get passport config: help.getPassportConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentForm.html" name="payments.getPaymentForm">Get payment form: payments.getPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getPaymentReceipt.html" name="payments.getPaymentReceipt">Get payment receipt: payments.getPaymentReceipt</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getLocated.html" name="contacts.getLocated">Get people nearby (geochats): contacts.getLocated</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPinnedDialogs.html" name="messages.getPinnedDialogs">Get pinned dialogs: messages.getPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPollResults.html" name="messages.getPollResults">Get poll results: messages.getPollResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getHistory.html" name="messages.getHistory">Get previous messages of a group: messages.getHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPrivacy.html" name="account.getPrivacy">Get privacy settings: account.getPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentLocations.html" name="messages.getRecentLocations">Get recent locations: messages.getRecentLocations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getRecentStickers.html" name="messages.getRecentStickers">Get recent stickers: messages.getRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getRecentMeUrls.html" name="help.getRecentMeUrls">Get recent t.me URLs: help.getRecentMeUrls</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.getSaved.html" name="contacts.getSaved">Get saved contacts: contacts.getSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSavedGifs.html" name="messages.getSavedGifs">Get saved gifs: messages.getSavedGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.getSavedInfo.html" name="payments.getSavedInfo">Get saved payments info: payments.getSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledHistory.html" name="messages.getScheduledHistory">Get scheduled history: messages.getScheduledHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getScheduledMessages.html" name="messages.getScheduledMessages">Get scheduled messages: messages.getScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getSearchCounters.html" name="messages.getSearchCounters">Get search counter: messages.getSearchCounters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getSecureValue.html" name="account.getSecureValue">Get secure value for telegram passport: account.getSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getConfig.html" name="help.getConfig">Get server configuration: help.getConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStatsURL.html" name="messages.getStatsURL">Get stats URL: messages.getStatsURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getAttachedStickers.html" name="messages.getAttachedStickers">Get stickers attachable to images: messages.getAttachedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getStickers.html" name="messages.getStickers">Get stickers: messages.getStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getSupportName.html" name="help.getSupportName">Get support name: help.getSupportName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWebAuthorizations.html" name="account.getWebAuthorizations">Get telegram web login authorizations: account.getWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTmpPassword.html" name="account.getTmpPassword">Get temporary password for buying products through bots: account.getTmpPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getBotCallbackAnswer.html" name="messages.getBotCallbackAnswer">Get the callback answer of a bot (after clicking a button): messages.getBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getAppChangelog.html" name="help.getAppChangelog">Get the changelog of this app: help.getAppChangelog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPasswordSettings.html" name="account.getPasswordSettings">Get the current 2FA settings: account.getPasswordSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getPassword.html" name="account.getPassword">Get the current password: account.getPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.exportMessageLink.html" name="channels.exportMessageLink">Get the link of a message in a channel: channels.exportMessageLink</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.getUserPhotos.html" name="photos.getUserPhotos">Get the profile photos of a user: photos.getUserPhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getPeerSettings.html" name="messages.getPeerSettings">Get the settings of  apeer: messages.getPeerSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getTheme.html" name="account.getTheme">Get theme information: account.getTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getUnreadMentions.html" name="messages.getUnreadMentions">Get unread mentions: messages.getUnreadMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getTermsOfServiceUpdate.html" name="help.getTermsOfServiceUpdate">Get updated TOS: help.getTermsOfServiceUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.getUserInfo.html" name="help.getUserInfo">Get user info: help.getUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPaper.html" name="account.getWallPaper">Get wallpaper info: account.getWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPage.html" name="messages.getWebPage">Get webpage preview: messages.getWebPage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getWebPagePreview.html" name="messages.getWebPagePreview">Get webpage preview: messages.getWebPagePreview</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.getDialogs.html" name="messages.getDialogs">Gets list of chats: you should use $MadelineProto->getDialogs() instead: https://docs.madelineproto.xyz/docs/DIALOGS.html: messages.getDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchGlobal.html" name="messages.searchGlobal">Global message search: messages.searchGlobal</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.hidePeerSettingsBar.html" name="messages.hidePeerSettingsBar">Hide peer settings bar: messages.hidePeerSettingsBar</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.importChatInvite.html" name="messages.importChatInvite">Import chat invite: messages.importChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/initConnection.html" name="initConnection">Initializes connection and save information on the user's device and application.: initConnection</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installTheme.html" name="account.installTheme">Install theme: account.installTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.installWallPaper.html" name="account.installWallPaper">Install wallpaper: account.installWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.cancelCode.html" name="auth.cancelCode">Invalidate sent phone code: auth.cancelCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithTakeout.html" name="invokeWithTakeout">Invoke method from takeout session: invokeWithTakeout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithLayer.html" name="invokeWithLayer">Invoke this method with layer X: invokeWithLayer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithMessagesRange.html" name="invokeWithMessagesRange">Invoke with messages range: invokeWithMessagesRange</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithoutUpdates.html" name="invokeWithoutUpdates">Invoke with method without returning updates in the socket: invokeWithoutUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsg.html" name="invokeAfterMsg">Invokes a query after successfull completion of one of the previous queries.: invokeAfterMsg</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.joinChannel.html" name="channels.joinChannel">Join a channel/supergroup: channels.joinChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.editBanned.html" name="channels.editBanned">Kick or ban a user from a channel/supergroup: channels.editBanned</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.leaveChannel.html" name="channels.leaveChannel">Leave a channel/supergroup: channels.leaveChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.saveAppLog.html" name="help.saveAppLog">Log data for developer of this app: help.saveAppLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readHistory.html" name="channels.readHistory">Mark channel/supergroup history as read: channels.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.readMessageContents.html" name="channels.readMessageContents">Mark channel/supergroup messages as read: channels.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.markDialogUnread.html" name="messages.markDialogUnread">Mark dialog as unread : messages.markDialogUnread</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMentions.html" name="messages.readMentions">Mark mentions as read: messages.readMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readMessageContents.html" name="messages.readMessageContents">Mark message as read: messages.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readEncryptedHistory.html" name="messages.readEncryptedHistory">Mark messages as read in secret chats: messages.readEncryptedHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readHistory.html" name="messages.readHistory">Mark messages as read: messages.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.receivedMessages.html" name="messages.receivedMessages">Mark messages as read: messages.receivedMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.readFeaturedStickers.html" name="messages.readFeaturedStickers">Mark new featured stickers as read: messages.readFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.receivedCall.html" name="phone.receivedCall">Notify server that you received a call (server will refuse all incoming calls until the current call is over): phone.receivedCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.toggleDialogPin.html" name="messages.toggleDialogPin">Pin or unpin dialog: messages.toggleDialogPin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.registerDevice.html" name="account.registerDevice">Register device for push notifications: account.registerDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uninstallStickerSet.html" name="messages.uninstallStickerSet">Remove a sticker set: messages.uninstallStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers.removeStickerFromSet.html" name="stickers.removeStickerFromSet">Remove sticker from stickerset: stickers.removeStickerFromSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderPinnedDialogs.html" name="messages.reorderPinnedDialogs">Reorder pinned dialogs: messages.reorderPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reorderStickerSets.html" name="messages.reorderStickerSets">Reorder sticker sets: messages.reorderStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.reportSpam.html" name="channels.reportSpam">Report a message in a supergroup/channel for spam: channels.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.report.html" name="messages.report">Report a message: messages.report</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportSpam.html" name="messages.reportSpam">Report a peer for spam: messages.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.reportEncryptedSpam.html" name="messages.reportEncryptedSpam">Report for spam a secret chat: messages.reportEncryptedSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.reportPeer.html" name="account.reportPeer">Report for spam: account.reportPeer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.requestUrlAuth.html" name="messages.requestUrlAuth">Request URL authorization: messages.requestUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resendPasswordEmail.html" name="account.resendPasswordEmail">Resend password recovery email: account.resendPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.resendCode.html" name="auth.resendCode">Resend the SMS verification code: auth.resendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetNotifySettings.html" name="account.resetNotifySettings">Reset all notification settings: account.resetNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWebAuthorizations.html" name="account.resetWebAuthorizations">Reset all telegram web login authorizations: account.resetWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetSaved.html" name="contacts.resetSaved">Reset saved contacts: contacts.resetSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resetTopPeerRating.html" name="contacts.resetTopPeerRating">Reset top peer rating for a certain category/peer: contacts.resetTopPeerRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.resetWallPapers.html" name="account.resetWallPapers">Reset wallpapers: account.resetWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsgs.html" name="invokeAfterMsgs">Result type returned by a current query.: invokeAfterMsgs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.getWallPapers.html" name="account.getWallPapers">Returns a list of available wallpapers.: account.getWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveGif.html" name="messages.saveGif">Save a GIF: messages.saveGif</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.saveDraft.html" name="messages.saveDraft">Save a message draft: messages.saveDraft</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveAutoDownloadSettings.html" name="account.saveAutoDownloadSettings">Save autodownload settings: account.saveAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.saveCallDebug.html" name="phone.saveCallDebug">Save call debugging info: phone.saveCallDebug</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveSecureValue.html" name="account.saveSecureValue">Save telegram passport secure value: account.saveSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveTheme.html" name="account.saveTheme">Save theme: account.saveTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.saveWallPaper.html" name="account.saveWallPaper">Save wallpaper: account.saveWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.search.html" name="contacts.search">Search contacts: contacts.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.searchGifs.html" name="messages.searchGifs">Search gifs: messages.searchGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.search.html" name="messages.search">Search peers or messages: messages.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.sendCustomRequest.html" name="bots.sendCustomRequest">Send a custom request to the bot API: bots.sendCustomRequest</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedFile.html" name="messages.sendEncryptedFile">Send a file to a secret chat: messages.sendEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMedia.html" name="messages.sendMedia">Send a media: messages.sendMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMessage.html" name="messages.sendMessage">Send a message: messages.sendMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncryptedService.html" name="messages.sendEncryptedService">Send a service message to a secret chat: messages.sendEncryptedService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendMultiMedia.html" name="messages.sendMultiMedia">Send an album: messages.sendMultiMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.requestPasswordRecovery.html" name="auth.requestPasswordRecovery">Send an email to recover the 2FA password: auth.requestPasswordRecovery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendConfirmPhoneCode.html" name="account.sendConfirmPhoneCode">Send confirmation phone code: account.sendConfirmPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyEmailCode.html" name="account.sendVerifyEmailCode">Send email verification code: account.sendVerifyEmailCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendInlineBotResult.html" name="messages.sendInlineBotResult">Send inline bot result obtained with messages.getInlineBotResults to the chat: messages.sendInlineBotResult</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendEncrypted.html" name="messages.sendEncrypted">Send message to secret chat: messages.sendEncrypted</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.sendVerifyPhoneCode.html" name="account.sendVerifyPhoneCode">Send phone verification code: account.sendVerifyPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScheduledMessages.html" name="messages.sendScheduledMessages">Send scheduled messages: messages.sendScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendScreenshotNotification.html" name="messages.sendScreenshotNotification">Send screenshot notification: messages.sendScreenshotNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setEncryptedTyping.html" name="messages.setEncryptedTyping">Send typing notification to secret chat: messages.setEncryptedTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.sendVote.html" name="messages.sendVote">Send vote: messages.sendVote</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots.answerWebhookJSONQuery.html" name="bots.answerWebhookJSONQuery">Send webhook request via bot API: bots.answerWebhookJSONQuery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setAccountTTL.html" name="account.setAccountTTL">Set account TTL: account.setAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setContactSignUpNotification.html" name="account.setContactSignUpNotification">Set contact sign up notification: account.setContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setDiscussionGroup.html" name="channels.setDiscussionGroup">Set discussion group of channel: channels.setDiscussionGroup</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone.setCallRating.html" name="phone.setCallRating">Set phone call rating: phone.setCallRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.setPrivacy.html" name="account.setPrivacy">Set privacy settings: account.setPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users.setSecureValueErrors.html" name="users.setSecureValueErrors">Set secure value error for telegram passport: users.setSecureValueErrors</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setInlineGameScore.html" name="messages.setInlineGameScore">Set the game score of an inline message: messages.setInlineGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.setGameScore.html" name="messages.setGameScore">Set the game score: messages.setGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.setStickers.html" name="channels.setStickers">Set the supergroup/channel stickerpack: channels.setStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help.setBotUpdatesStatus.html" name="help.setBotUpdatesStatus">Set the update status of webhook: help.setBotUpdatesStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.startBot.html" name="messages.startBot">Start a bot: messages.startBot</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.initTakeoutSession.html" name="account.initTakeoutSession">Start account exporting session: account.initTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.unregisterDevice.html" name="account.unregisterDevice">Stop sending PUSH notifications to app: account.unregisterDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSignatures.html" name="channels.toggleSignatures">Toggle channel signatures: channels.toggleSignatures</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.toggleSlowMode.html" name="channels.toggleSlowMode">Toggle slow mode: channels.toggleSlowMode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.toggleTopPeers.html" name="contacts.toggleTopPeers">Toggle top peers: contacts.toggleTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.unblock.html" name="contacts.unblock">Unblock a user: contacts.unblock</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateStatus.html" name="account.updateStatus">Update online status: account.updateStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.updatePinnedMessage.html" name="messages.updatePinnedMessage">Update pinned message: messages.updatePinnedMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateProfile.html" name="account.updateProfile">Update profile info: account.updateProfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels.updateUsername.html" name="channels.updateUsername">Update the username of a supergroup/channel: channels.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateTheme.html" name="account.updateTheme">Update theme: account.updateTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.updateUsername.html" name="account.updateUsername">Update this user's username: account.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadMedia.html" name="messages.uploadMedia">Upload a file without sending it to anyone: messages.uploadMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.uploadEncryptedFile.html" name="messages.uploadEncryptedFile">Upload a secret chat file without sending it to anyone: messages.uploadEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos.uploadProfilePhoto.html" name="photos.uploadProfilePhoto">Upload profile photo: photos.uploadProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadTheme.html" name="account.uploadTheme">Upload theme: account.uploadTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.uploadWallPaper.html" name="account.uploadWallPaper">Upload wallpaper: account.uploadWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.sendCode.html" name="auth.sendCode">Use phoneLogin instead: auth.sendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth.recoverPassword.html" name="auth.recoverPassword">Use the code that was emailed to you after running $MadelineProto->auth->requestPasswordRecovery to login to your account: auth.recoverPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments.validateRequestedInfo.html" name="payments.validateRequestedInfo">Validate requested payment info: payments.validateRequestedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyEmail.html" name="account.verifyEmail">Verify email address: account.verifyEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account.verifyPhone.html" name="account.verifyPhone">Verify phone number: account.verifyPhone</a>
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
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts.resolveUsername.html" name="contacts.resolveUsername">You cannot use this method directly, use the resolveUsername, getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info): contacts.resolveUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getCdnFile.html" name="upload.getCdnFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getCdnFileHashes.html" name="upload.getCdnFileHashes">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.getFile.html" name="upload.getFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.getFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.reuploadCdnFile.html" name="upload.reuploadCdnFile">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.reuploadCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.saveBigFilePart.html" name="upload.saveBigFilePart">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveBigFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload.saveFilePart.html" name="upload.saveFilePart">You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages.receivedQueue.html" name="messages.receivedQueue">You cannot use this method directly: messages.receivedQueue</a>
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
* [`tests/testing.php`](https://github.com/danog/MadelineProto/blob/master/tests/testing.php) - examples for making/receiving calls, making secret chats, sending secret chat messages, videos, audios, voice recordings, gifs, stickers, photos, sending normal messages, videos, audios, voice recordings, gifs, stickers, photos.
* [`bot.php`](https://github.com/danog/MadelineProto/blob/master/examples/bot.php) - examples for sending normal messages, downloading any media
* [`secret_bot.php`](https://github.com/danog/MadelineProto/blob/master/examples/secret_bot.php) - secret chat bot
* [`pipesbot.php`](https://github.com/danog/MadelineProto/blob/master/examples/pipesbot.php) - examples for creating inline bots and using other inline bots via a userbot


