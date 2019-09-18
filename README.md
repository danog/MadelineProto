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

    $me = yield $MadelineProto->get_self();

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
    * [Get the full participant list of a channel/group/supergroup](https://docs.madelineproto.xyz/get_pwr_chat.html)
    * [Get full info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_full_info.html)
    * [Get info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_info.html)
    * [Get info about the currently logged-in user](https://docs.madelineproto.xyz/get_self.html)
    * [Upload or download files up to 1.5 GB](https://docs.madelineproto.xyz/docs/FILES.html)
    * [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)
    * [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_acceptUrlAuth.html" name="messages_acceptUrlAuth">Accept URL authorization: messages.acceptUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_acceptContact.html" name="contacts_acceptContact">Accept contact: contacts.acceptContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_acceptAuthorization.html" name="account_acceptAuthorization">Accept telegram passport authorization: account.acceptAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_acceptTermsOfService.html" name="help_acceptTermsOfService">Accept telegram's TOS: help.acceptTermsOfService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_installStickerSet.html" name="messages_installStickerSet">Add a sticker set: messages.installStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_faveSticker.html" name="messages_faveSticker">Add a sticker to favorites: messages.faveSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_saveRecentSticker.html" name="messages_saveRecentSticker">Add a sticker to recent stickers: messages.saveRecentSticker</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_addChatUser.html" name="messages_addChatUser">Add a user to a normal chat (use channels->inviteToChannel for supergroups): messages.addChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_addContact.html" name="contacts_addContact">Add contact: contacts.addContact</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_importContacts.html" name="contacts_importContacts">Add phone number as contact: contacts.importContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers_addStickerToSet.html" name="stickers_addStickerToSet">Add sticker to stickerset: stickers.addStickerToSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_inviteToChannel.html" name="channels_inviteToChannel">Add users to channel/supergroup: channels.inviteToChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_block.html" name="contacts_block">Block a user: contacts.block</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getAuthorizationForm.html" name="account_getAuthorizationForm">Bots only: get telegram passport authorization form: account.getAuthorizationForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_sendPaymentForm.html" name="payments_sendPaymentForm">Bots only: send payment form: payments.sendPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setBotPrecheckoutResults.html" name="messages_setBotPrecheckoutResults">Bots only: set precheckout results: messages.setBotPrecheckoutResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setBotShippingResults.html" name="messages_setBotShippingResults">Bots only: set shipping results: messages.setBotShippingResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setBotCallbackAnswer.html" name="messages_setBotCallbackAnswer">Bots only: set the callback answer (after a button was clicked): messages.setBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setInlineBotResults.html" name="messages_setInlineBotResults">Bots only: set the results of an inline query: messages.setInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getInlineBotResults.html" name="messages_getInlineBotResults">Call inline bot: messages.getInlineBotResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_cancelPasswordEmail.html" name="account_cancelPasswordEmail">Cancel password recovery email: account.cancelPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateNotifySettings.html" name="account_updateNotifySettings">Change notification settings: account.updateNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers_changeStickerPosition.html" name="stickers_changeStickerPosition">Change sticker position in photo: stickers.changeStickerPosition</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_changePhone.html" name="account_changePhone">Change the phone number associated to this account: account.changePhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_sendChangePhoneCode.html" name="account_sendChangePhoneCode">Change the phone number: account.sendChangePhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos_updateProfilePhoto.html" name="photos_updateProfilePhoto">Change the profile photo: photos.updateProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setTyping.html" name="messages_setTyping">Change typing status: messages.setTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getMessageEditData.html" name="messages_getMessageEditData">Check if about to edit a message or a media caption: messages.getMessageEditData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_checkChatInvite.html" name="messages_checkChatInvite">Check if an invitation link is valid: messages.checkChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_checkUsername.html" name="account_checkUsername">Check if this username is available: account.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_checkUsername.html" name="channels_checkUsername">Check if this username is free and can be assigned to a channel/supergroup: channels.checkUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_clearAllDrafts.html" name="messages_clearAllDrafts">Clear all drafts: messages.clearAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_clearRecentStickers.html" name="messages_clearRecentStickers">Clear all recent stickers: messages.clearRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_clearSavedInfo.html" name="payments_clearSavedInfo">Clear saved payments info: payments.clearSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_confirmPasswordEmail.html" name="account_confirmPasswordEmail">Confirm password recovery using email: account.confirmPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_confirmPhone.html" name="account_confirmPhone">Confirm this phone number is associated to this account, obtain phone_code_hash from sendConfirmPhoneCode: account.confirmPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getContactSignUpNotification.html" name="account_getContactSignUpNotification">Contact signup notification setting value: account.getContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_migrateChat.html" name="messages_migrateChat">Convert chat to supergroup: messages.migrateChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_createChat.html" name="messages_createChat">Create a chat (not supergroup): messages.createChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_createTheme.html" name="account_createTheme">Create a theme: account.createTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_createChannel.html" name="channels_createChannel">Create channel/supergroup: channels.createChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers_createStickerSet.html" name="stickers_createStickerSet">Create stickerset: stickers.createStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resetAuthorization.html" name="account_resetAuthorization">Delete a certain session: account.resetAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resetWebAuthorization.html" name="account_resetWebAuthorization">Delete a certain telegram web login authorization: account.resetWebAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_deleteChannel.html" name="channels_deleteChannel">Delete a channel/supergroup: channels.deleteChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_deleteChatUser.html" name="messages_deleteChatUser">Delete a user from a chat (not supergroup): messages.deleteChatUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_resetAuthorizations.html" name="auth_resetAuthorizations">Delete all logged-in sessions.: auth.resetAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_deleteUserHistory.html" name="channels_deleteUserHistory">Delete all messages of a user in a channel/supergroup: channels.deleteUserHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_dropTempAuthKeys.html" name="auth_dropTempAuthKeys">Delete all temporary authorization keys except the ones provided: auth.dropTempAuthKeys</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_deleteMessages.html" name="channels_deleteMessages">Delete channel/supergroup messages: channels.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_deleteHistory.html" name="messages_deleteHistory">Delete chat history: messages.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_deleteByPhones.html" name="contacts_deleteByPhones">Delete contacts by phones: contacts.deleteByPhones</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders_deleteFolder.html" name="folders_deleteFolder">Delete folder: folders.deleteFolder</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_deleteMessages.html" name="messages_deleteMessages">Delete messages: messages.deleteMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_deleteContacts.html" name="contacts_deleteContacts">Delete multiple contacts: contacts.deleteContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos_deletePhotos.html" name="photos_deletePhotos">Delete profile photos: photos.deletePhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_deleteScheduledMessages.html" name="messages_deleteScheduledMessages">Delete scheduled messages: messages.deleteScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_deleteSecureValue.html" name="account_deleteSecureValue">Delete secure telegram passport value: account.deleteSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_deleteHistory.html" name="channels_deleteHistory">Delete the history of a supergroup/channel: channels.deleteHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_deleteAccount.html" name="account_deleteAccount">Delete this account: account.deleteAccount</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateDeviceLocked.html" name="account_updateDeviceLocked">Disable all notifications for a certain period: account.updateDeviceLocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_getWebFile.html" name="upload_getWebFile">Download a file through telegram: upload.getWebFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editMessage.html" name="messages_editMessage">Edit a message: messages.editMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editInlineBotMessage.html" name="messages_editInlineBotMessage">Edit a sent inline message: messages.editInlineBotMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editAdmin.html" name="channels_editAdmin">Edit admin permissions of a user in a channel/supergroup: channels.editAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editChatAdmin.html" name="messages_editChatAdmin">Edit admin permissions: messages.editChatAdmin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editChatAbout.html" name="messages_editChatAbout">Edit chat info: messages.editChatAbout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editCreator.html" name="channels_editCreator">Edit creator of channel: channels.editCreator</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editChatDefaultBannedRights.html" name="messages_editChatDefaultBannedRights">Edit default rights of chat: messages.editChatDefaultBannedRights</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/folders_editPeerFolders.html" name="folders_editPeerFolders">Edit folder: folders.editPeerFolders</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editLocation.html" name="channels_editLocation">Edit location (geochats): channels.editLocation</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editChatPhoto.html" name="messages_editChatPhoto">Edit the photo of a normal chat (not supergroup): messages.editChatPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editPhoto.html" name="channels_editPhoto">Edit the photo of a supergroup/channel: channels.editPhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_editChatTitle.html" name="messages_editChatTitle">Edit the title of a normal chat (not supergroup): messages.editChatTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editTitle.html" name="channels_editTitle">Edit the title of a supergroup/channel: channels.editTitle</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_editUserInfo.html" name="help_editUserInfo">Edit user info: help.editUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_togglePreHistoryHidden.html" name="channels_togglePreHistoryHidden">Enable or disable hidden history for new channel/supergroup users: channels.togglePreHistoryHidden</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_exportChatInvite.html" name="messages_exportChatInvite">Export chat invite : messages.exportChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_searchStickerSets.html" name="messages_searchStickerSets">Find a sticker set: messages.searchStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_finishTakeoutSession.html" name="account_finishTakeoutSession">Finish account exporting session: account.finishTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_forwardMessages.html" name="messages_forwardMessages">Forward messages: messages.forwardMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getCdnConfig.html" name="help_getCdnConfig">Get CDN configuration: help.getCdnConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getStickerSet.html" name="messages_getStickerSet">Get a stickerset: messages.getStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getAccountTTL.html" name="account_getAccountTTL">Get account TTL: account.getAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getAdminLog.html" name="channels_getAdminLog">Get admin log of a channel/supergroup: channels.getAdminLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getArchivedStickers.html" name="messages_getArchivedStickers">Get all archived stickers: messages.getArchivedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getLeftChannels.html" name="channels_getLeftChannels">Get all channels you left: channels.getLeftChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getAllChats.html" name="messages_getAllChats">Get all chats (not supergroups or channels): messages.getAllChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getContacts.html" name="contacts_getContacts">Get all contacts: contacts.getContacts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getAuthorizations.html" name="account_getAuthorizations">Get all logged-in authorizations: account.getAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getAllDrafts.html" name="messages_getAllDrafts">Get all message drafts: messages.getAllDrafts</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getAllSecureValues.html" name="account_getAllSecureValues">Get all secure telegram passport values: account.getAllSecureValues</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getAllStickers.html" name="messages_getAllStickers">Get all stickerpacks: messages.getAllStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getAdminedPublicChannels.html" name="channels_getAdminedPublicChannels">Get all supergroups/channels where you're admin: channels.getAdminedPublicChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getMessagesViews.html" name="messages_getMessagesViews">Get and increase message views: messages.getMessagesViews</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getAppConfig.html" name="help_getAppConfig">Get app config: help.getAppConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getAutoDownloadSettings.html" name="account_getAutoDownloadSettings">Get autodownload settings: account.getAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack_getLanguages.html" name="langpack_getLanguages">Get available languages: langpack.getLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getBlocked.html" name="contacts_getBlocked">Get blocked users: contacts.getBlocked</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_getCallConfig.html" name="phone_getCallConfig">Get call configuration: phone.getCallConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getMessages.html" name="channels_getMessages">Get channel/supergroup messages: channels.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getParticipants.html" name="channels_getParticipants">Get channel/supergroup participants (you should use `$MadelineProto->get_pwr_chat($id)` instead): channels.getParticipants</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getCommonChats.html" name="messages_getCommonChats">Get chats in common with a user: messages.getCommonChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getContactIDs.html" name="contacts_getContactIDs">Get contacts by IDs: contacts.getContactIDs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getDeepLinkInfo.html" name="help_getDeepLinkInfo">Get deep link info: help.getDeepLinkInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getPeerDialogs.html" name="messages_getPeerDialogs">Get dialog info of peers: messages.getPeerDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getDialogUnreadMarks.html" name="messages_getDialogUnreadMarks">Get dialogs marked as unread manually: messages.getDialogUnreadMarks</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getDocumentByHash.html" name="messages_getDocumentByHash">Get document by SHA256 hash: messages.getDocumentByHash</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getEmojiURL.html" name="messages_getEmojiURL">Get emoji URL: messages.getEmojiURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getEmojiKeywordsDifference.html" name="messages_getEmojiKeywordsDifference">Get emoji keyword difference: messages.getEmojiKeywordsDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getEmojiKeywordsLanguages.html" name="messages_getEmojiKeywordsLanguages">Get emoji keyword languages: messages.getEmojiKeywordsLanguages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getEmojiKeywords.html" name="messages_getEmojiKeywords">Get emoji keywords: messages.getEmojiKeywords</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getFavedStickers.html" name="messages_getFavedStickers">Get favorite stickers: messages.getFavedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getFeaturedStickers.html" name="messages_getFeaturedStickers">Get featured stickers: messages.getFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_getFileHashes.html" name="upload_getFileHashes">Get file hashes: upload.getFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getGroupsForDiscussion.html" name="channels_getGroupsForDiscussion">Get groups for discussion: channels.getGroupsForDiscussion</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getInlineGameHighScores.html" name="messages_getInlineGameHighScores">Get high scores of a game sent in an inline message: messages.getInlineGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getGameHighScores.html" name="messages_getGameHighScores">Get high scores of a game: messages.getGameHighScores</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getParticipant.html" name="channels_getParticipant">Get info about a certain channel/supergroup participant: channels.getParticipant</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getAppUpdate.html" name="help_getAppUpdate">Get info about app updates: help.getAppUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getChats.html" name="messages_getChats">Get info about chats: messages.getChats</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getChannels.html" name="channels_getChannels">Get info about multiple channels/supergroups: channels.getChannels</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users_getUsers.html" name="users_getUsers">Get info about users: users.getUsers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getSupport.html" name="help_getSupport">Get info of support user: help.getSupport</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getProxyData.html" name="help_getProxyData">Get information about the current proxy: help.getProxyData</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getThemes.html" name="account_getThemes">Get installed themes: account.getThemes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getInviteText.html" name="help_getInviteText">Get invitation text: help.getInviteText</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack_getStrings.html" name="langpack_getStrings">Get language pack strings: langpack.getStrings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack_getDifference.html" name="langpack_getDifference">Get language pack updates: langpack.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack_getLangPack.html" name="langpack_getLangPack">Get language pack: langpack.getLangPack</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/langpack_getLanguage.html" name="langpack_getLanguage">Get language: langpack.getLanguage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getMaskStickers.html" name="messages_getMaskStickers">Get masks: messages.getMaskStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getSplitRanges.html" name="messages_getSplitRanges">Get message ranges to fetch: messages.getSplitRanges</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getMessages.html" name="messages_getMessages">Get messages: messages.getMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getTopPeers.html" name="contacts_getTopPeers">Get most used chats: contacts.getTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getNearestDc.html" name="help_getNearestDc">Get nearest datacenter: help.getNearestDc</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getNotifyExceptions.html" name="account_getNotifyExceptions">Get notification exceptions: account.getNotifyExceptions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getNotifySettings.html" name="account_getNotifySettings">Get notification settings: account.getNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getStatuses.html" name="contacts_getStatuses">Get online status of all users: contacts.getStatuses</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getOnlines.html" name="messages_getOnlines">Get online users: messages.getOnlines</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getPassportConfig.html" name="help_getPassportConfig">Get passport config: help.getPassportConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_getPaymentForm.html" name="payments_getPaymentForm">Get payment form: payments.getPaymentForm</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_getPaymentReceipt.html" name="payments_getPaymentReceipt">Get payment receipt: payments.getPaymentReceipt</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getLocated.html" name="contacts_getLocated">Get people nearby (geochats): contacts.getLocated</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getPinnedDialogs.html" name="messages_getPinnedDialogs">Get pinned dialogs: messages.getPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getPollResults.html" name="messages_getPollResults">Get poll results: messages.getPollResults</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getHistory.html" name="messages_getHistory">Get previous messages of a group: messages.getHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getPrivacy.html" name="account_getPrivacy">Get privacy settings: account.getPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getRecentLocations.html" name="messages_getRecentLocations">Get recent locations: messages.getRecentLocations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getRecentStickers.html" name="messages_getRecentStickers">Get recent stickers: messages.getRecentStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getRecentMeUrls.html" name="help_getRecentMeUrls">Get recent t.me URLs: help.getRecentMeUrls</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_getSaved.html" name="contacts_getSaved">Get saved contacts: contacts.getSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getSavedGifs.html" name="messages_getSavedGifs">Get saved gifs: messages.getSavedGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_getSavedInfo.html" name="payments_getSavedInfo">Get saved payments info: payments.getSavedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getScheduledHistory.html" name="messages_getScheduledHistory">Get scheduled history: messages.getScheduledHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getScheduledMessages.html" name="messages_getScheduledMessages">Get scheduled messages: messages.getScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getSearchCounters.html" name="messages_getSearchCounters">Get search counter: messages.getSearchCounters</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getSecureValue.html" name="account_getSecureValue">Get secure value for telegram passport: account.getSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getConfig.html" name="help_getConfig">Get server configuration: help.getConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getStatsURL.html" name="messages_getStatsURL">Get stats URL: messages.getStatsURL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getAttachedStickers.html" name="messages_getAttachedStickers">Get stickers attachable to images: messages.getAttachedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getStickers.html" name="messages_getStickers">Get stickers: messages.getStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getSupportName.html" name="help_getSupportName">Get support name: help.getSupportName</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getWebAuthorizations.html" name="account_getWebAuthorizations">Get telegram web login authorizations: account.getWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getTmpPassword.html" name="account_getTmpPassword">Get temporary password for buying products through bots: account.getTmpPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getBotCallbackAnswer.html" name="messages_getBotCallbackAnswer">Get the callback answer of a bot (after clicking a button): messages.getBotCallbackAnswer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getAppChangelog.html" name="help_getAppChangelog">Get the changelog of this app: help.getAppChangelog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getPasswordSettings.html" name="account_getPasswordSettings">Get the current 2FA settings: account.getPasswordSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getPassword.html" name="account_getPassword">Get the current password: account.getPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_exportMessageLink.html" name="channels_exportMessageLink">Get the link of a message in a channel: channels.exportMessageLink</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos_getUserPhotos.html" name="photos_getUserPhotos">Get the profile photos of a user: photos.getUserPhotos</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getPeerSettings.html" name="messages_getPeerSettings">Get the settings of  apeer: messages.getPeerSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getTheme.html" name="account_getTheme">Get theme information: account.getTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getUnreadMentions.html" name="messages_getUnreadMentions">Get unread mentions: messages.getUnreadMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getTermsOfServiceUpdate.html" name="help_getTermsOfServiceUpdate">Get updated TOS: help.getTermsOfServiceUpdate</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_getUserInfo.html" name="help_getUserInfo">Get user info: help.getUserInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getWallPaper.html" name="account_getWallPaper">Get wallpaper info: account.getWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getWebPage.html" name="messages_getWebPage">Get webpage preview: messages.getWebPage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getWebPagePreview.html" name="messages_getWebPagePreview">Get webpage preview: messages.getWebPagePreview</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getDialogs.html" name="messages_getDialogs">Gets list of chats: you should use $MadelineProto->get_dialogs() instead: https://docs.madelineproto.xyz/docs/DIALOGS.html: messages.getDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_searchGlobal.html" name="messages_searchGlobal">Global message search: messages.searchGlobal</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_hidePeerSettingsBar.html" name="messages_hidePeerSettingsBar">Hide peer settings bar: messages.hidePeerSettingsBar</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_importChatInvite.html" name="messages_importChatInvite">Import chat invite: messages.importChatInvite</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/initConnection.html" name="initConnection">Initializes connection and save information on the user's device and application.: initConnection</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_installTheme.html" name="account_installTheme">Install theme: account.installTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_installWallPaper.html" name="account_installWallPaper">Install wallpaper: account.installWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_cancelCode.html" name="auth_cancelCode">Invalidate sent phone code: auth.cancelCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithTakeout.html" name="invokeWithTakeout">Invoke method from takeout session: invokeWithTakeout</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithLayer.html" name="invokeWithLayer">Invoke this method with layer X: invokeWithLayer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithMessagesRange.html" name="invokeWithMessagesRange">Invoke with messages range: invokeWithMessagesRange</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeWithoutUpdates.html" name="invokeWithoutUpdates">Invoke with method without returning updates in the socket: invokeWithoutUpdates</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsg.html" name="invokeAfterMsg">Invokes a query after successfull completion of one of the previous queries.: invokeAfterMsg</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_joinChannel.html" name="channels_joinChannel">Join a channel/supergroup: channels.joinChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_editBanned.html" name="channels_editBanned">Kick or ban a user from a channel/supergroup: channels.editBanned</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_leaveChannel.html" name="channels_leaveChannel">Leave a channel/supergroup: channels.leaveChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_saveAppLog.html" name="help_saveAppLog">Log data for developer of this app: help.saveAppLog</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_readHistory.html" name="channels_readHistory">Mark channel/supergroup history as read: channels.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_readMessageContents.html" name="channels_readMessageContents">Mark channel/supergroup messages as read: channels.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_markDialogUnread.html" name="messages_markDialogUnread">Mark dialog as unread : messages.markDialogUnread</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_readMentions.html" name="messages_readMentions">Mark mentions as read: messages.readMentions</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_readMessageContents.html" name="messages_readMessageContents">Mark message as read: messages.readMessageContents</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_readEncryptedHistory.html" name="messages_readEncryptedHistory">Mark messages as read in secret chats: messages.readEncryptedHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_readHistory.html" name="messages_readHistory">Mark messages as read: messages.readHistory</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_receivedMessages.html" name="messages_receivedMessages">Mark messages as read: messages.receivedMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_readFeaturedStickers.html" name="messages_readFeaturedStickers">Mark new featured stickers as read: messages.readFeaturedStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_receivedCall.html" name="phone_receivedCall">Notify server that you received a call (server will refuse all incoming calls until the current call is over): phone.receivedCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_toggleDialogPin.html" name="messages_toggleDialogPin">Pin or unpin dialog: messages.toggleDialogPin</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_registerDevice.html" name="account_registerDevice">Register device for push notifications: account.registerDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_uninstallStickerSet.html" name="messages_uninstallStickerSet">Remove a sticker set: messages.uninstallStickerSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/stickers_removeStickerFromSet.html" name="stickers_removeStickerFromSet">Remove sticker from stickerset: stickers.removeStickerFromSet</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_reorderPinnedDialogs.html" name="messages_reorderPinnedDialogs">Reorder pinned dialogs: messages.reorderPinnedDialogs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_reorderStickerSets.html" name="messages_reorderStickerSets">Reorder sticker sets: messages.reorderStickerSets</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_reportSpam.html" name="channels_reportSpam">Report a message in a supergroup/channel for spam: channels.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_report.html" name="messages_report">Report a message: messages.report</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_reportSpam.html" name="messages_reportSpam">Report a peer for spam: messages.reportSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_reportEncryptedSpam.html" name="messages_reportEncryptedSpam">Report for spam a secret chat: messages.reportEncryptedSpam</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_reportPeer.html" name="account_reportPeer">Report for spam: account.reportPeer</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_requestUrlAuth.html" name="messages_requestUrlAuth">Request URL authorization: messages.requestUrlAuth</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resendPasswordEmail.html" name="account_resendPasswordEmail">Resend password recovery email: account.resendPasswordEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_resendCode.html" name="auth_resendCode">Resend the SMS verification code: auth.resendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resetNotifySettings.html" name="account_resetNotifySettings">Reset all notification settings: account.resetNotifySettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resetWebAuthorizations.html" name="account_resetWebAuthorizations">Reset all telegram web login authorizations: account.resetWebAuthorizations</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_resetSaved.html" name="contacts_resetSaved">Reset saved contacts: contacts.resetSaved</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_resetTopPeerRating.html" name="contacts_resetTopPeerRating">Reset top peer rating for a certain category/peer: contacts.resetTopPeerRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_resetWallPapers.html" name="account_resetWallPapers">Reset wallpapers: account.resetWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/invokeAfterMsgs.html" name="invokeAfterMsgs">Result type returned by a current query.: invokeAfterMsgs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_getWallPapers.html" name="account_getWallPapers">Returns a list of available wallpapers.: account.getWallPapers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_saveGif.html" name="messages_saveGif">Save a GIF: messages.saveGif</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_saveDraft.html" name="messages_saveDraft">Save a message draft: messages.saveDraft</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_saveAutoDownloadSettings.html" name="account_saveAutoDownloadSettings">Save autodownload settings: account.saveAutoDownloadSettings</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_saveCallDebug.html" name="phone_saveCallDebug">Save call debugging info: phone.saveCallDebug</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_saveSecureValue.html" name="account_saveSecureValue">Save telegram passport secure value: account.saveSecureValue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_saveTheme.html" name="account_saveTheme">Save theme: account.saveTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_saveWallPaper.html" name="account_saveWallPaper">Save wallpaper: account.saveWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_search.html" name="contacts_search">Search contacts: contacts.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_searchGifs.html" name="messages_searchGifs">Search gifs: messages.searchGifs</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_search.html" name="messages_search">Search peers or messages: messages.search</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots_sendCustomRequest.html" name="bots_sendCustomRequest">Send a custom request to the bot API: bots.sendCustomRequest</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendEncryptedFile.html" name="messages_sendEncryptedFile">Send a file to a secret chat: messages.sendEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendMedia.html" name="messages_sendMedia">Send a media: messages.sendMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendMessage.html" name="messages_sendMessage">Send a message: messages.sendMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendEncryptedService.html" name="messages_sendEncryptedService">Send a service message to a secret chat: messages.sendEncryptedService</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendMultiMedia.html" name="messages_sendMultiMedia">Send an album: messages.sendMultiMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_requestPasswordRecovery.html" name="auth_requestPasswordRecovery">Send an email to recover the 2FA password: auth.requestPasswordRecovery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_sendConfirmPhoneCode.html" name="account_sendConfirmPhoneCode">Send confirmation phone code: account.sendConfirmPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_sendVerifyEmailCode.html" name="account_sendVerifyEmailCode">Send email verification code: account.sendVerifyEmailCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendInlineBotResult.html" name="messages_sendInlineBotResult">Send inline bot result obtained with messages.getInlineBotResults to the chat: messages.sendInlineBotResult</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendEncrypted.html" name="messages_sendEncrypted">Send message to secret chat: messages.sendEncrypted</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_sendVerifyPhoneCode.html" name="account_sendVerifyPhoneCode">Send phone verification code: account.sendVerifyPhoneCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendScheduledMessages.html" name="messages_sendScheduledMessages">Send scheduled messages: messages.sendScheduledMessages</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendScreenshotNotification.html" name="messages_sendScreenshotNotification">Send screenshot notification: messages.sendScreenshotNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setEncryptedTyping.html" name="messages_setEncryptedTyping">Send typing notification to secret chat: messages.setEncryptedTyping</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_sendVote.html" name="messages_sendVote">Send vote: messages.sendVote</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/bots_answerWebhookJSONQuery.html" name="bots_answerWebhookJSONQuery">Send webhook request via bot API: bots.answerWebhookJSONQuery</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_setAccountTTL.html" name="account_setAccountTTL">Set account TTL: account.setAccountTTL</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_setContactSignUpNotification.html" name="account_setContactSignUpNotification">Set contact sign up notification: account.setContactSignUpNotification</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_setDiscussionGroup.html" name="channels_setDiscussionGroup">Set discussion group of channel: channels.setDiscussionGroup</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_setCallRating.html" name="phone_setCallRating">Set phone call rating: phone.setCallRating</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_setPrivacy.html" name="account_setPrivacy">Set privacy settings: account.setPrivacy</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users_setSecureValueErrors.html" name="users_setSecureValueErrors">Set secure value error for telegram passport: users.setSecureValueErrors</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setInlineGameScore.html" name="messages_setInlineGameScore">Set the game score of an inline message: messages.setInlineGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_setGameScore.html" name="messages_setGameScore">Set the game score: messages.setGameScore</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_setStickers.html" name="channels_setStickers">Set the supergroup/channel stickerpack: channels.setStickers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/help_setBotUpdatesStatus.html" name="help_setBotUpdatesStatus">Set the update status of webhook: help.setBotUpdatesStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_startBot.html" name="messages_startBot">Start a bot: messages.startBot</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_initTakeoutSession.html" name="account_initTakeoutSession">Start account exporting session: account.initTakeoutSession</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_unregisterDevice.html" name="account_unregisterDevice">Stop sending PUSH notifications to app: account.unregisterDevice</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_toggleSignatures.html" name="channels_toggleSignatures">Toggle channel signatures: channels.toggleSignatures</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_toggleSlowMode.html" name="channels_toggleSlowMode">Toggle slow mode: channels.toggleSlowMode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_toggleTopPeers.html" name="contacts_toggleTopPeers">Toggle top peers: contacts.toggleTopPeers</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_unblock.html" name="contacts_unblock">Unblock a user: contacts.unblock</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateStatus.html" name="account_updateStatus">Update online status: account.updateStatus</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_updatePinnedMessage.html" name="messages_updatePinnedMessage">Update pinned message: messages.updatePinnedMessage</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateProfile.html" name="account_updateProfile">Update profile info: account.updateProfile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_updateUsername.html" name="channels_updateUsername">Update the username of a supergroup/channel: channels.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateTheme.html" name="account_updateTheme">Update theme: account.updateTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updateUsername.html" name="account_updateUsername">Update this user's username: account.updateUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_uploadMedia.html" name="messages_uploadMedia">Upload a file without sending it to anyone: messages.uploadMedia</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_uploadEncryptedFile.html" name="messages_uploadEncryptedFile">Upload a secret chat file without sending it to anyone: messages.uploadEncryptedFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/photos_uploadProfilePhoto.html" name="photos_uploadProfilePhoto">Upload profile photo: photos.uploadProfilePhoto</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_uploadTheme.html" name="account_uploadTheme">Upload theme: account.uploadTheme</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_uploadWallPaper.html" name="account_uploadWallPaper">Upload wallpaper: account.uploadWallPaper</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_sendCode.html" name="auth_sendCode">Use phone_login instead: auth.sendCode</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_recoverPassword.html" name="auth_recoverPassword">Use the code that was emailed to you after running $MadelineProto->auth->requestPasswordRecovery to login to your account: auth.recoverPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/payments_validateRequestedInfo.html" name="payments_validateRequestedInfo">Validate requested payment info: payments.validateRequestedInfo</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_verifyEmail.html" name="account_verifyEmail">Verify email address: account.verifyEmail</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_verifyPhone.html" name="account_verifyPhone">Verify phone number: account.verifyPhone</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_bindTempAuthKey.html" name="auth_bindTempAuthKey">You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info: auth.bindTempAuthKey</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getDhConfig.html" name="messages_getDhConfig">You cannot use this method directly, instead use $MadelineProto->get_dh_config();: messages.getDhConfig</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_acceptEncryption.html" name="messages_acceptEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.acceptEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_discardEncryption.html" name="messages_discardEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.discardEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_requestEncryption.html" name="messages_requestEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats: messages.requestEncryption</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates_getChannelDifference.html" name="updates_getChannelDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getChannelDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates_getDifference.html" name="updates_getDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getDifference</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/updates_getState.html" name="updates_getState">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates: updates.getState</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_acceptCall.html" name="phone_acceptCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.acceptCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_confirmCall.html" name="phone_confirmCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.confirmCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_discardCall.html" name="phone_discardCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.discardCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/phone_requestCall.html" name="phone_requestCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls: phone.requestCall</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_exportAuthorization.html" name="auth_exportAuthorization">You cannot use this method directly, use $MadelineProto->export_authorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html: auth.exportAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_importAuthorization.html" name="auth_importAuthorization">You cannot use this method directly, use $MadelineProto->import_authorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html: auth.importAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_importBotAuthorization.html" name="auth_importBotAuthorization">You cannot use this method directly, use the bot_login method instead (see https://docs.madelineproto.xyz for more info): auth.importBotAuthorization</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_checkPassword.html" name="auth_checkPassword">You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info): auth.checkPassword</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_signIn.html" name="auth_signIn">You cannot use this method directly, use the complete_phone_login method instead (see https://docs.madelineproto.xyz for more info): auth.signIn</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_signUp.html" name="auth_signUp">You cannot use this method directly, use the complete_signup method instead (see https://docs.madelineproto.xyz for more info): auth.signUp</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/channels_getFullChannel.html" name="channels_getFullChannel">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info): channels.getFullChannel</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_getFullChat.html" name="messages_getFullChat">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info): messages.getFullChat</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/users_getFullUser.html" name="users_getFullUser">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info): users.getFullUser</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/auth_logOut.html" name="auth_logOut">You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info): auth.logOut</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/contacts_resolveUsername.html" name="contacts_resolveUsername">You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info): contacts.resolveUsername</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_getCdnFile.html" name="upload_getCdnFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_getCdnFileHashes.html" name="upload_getCdnFileHashes">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.getCdnFileHashes</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_getFile.html" name="upload_getFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.getFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_reuploadCdnFile.html" name="upload_reuploadCdnFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.reuploadCdnFile</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_saveBigFilePart.html" name="upload_saveBigFilePart">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveBigFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/upload_saveFilePart.html" name="upload_saveFilePart">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info: upload.saveFilePart</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/messages_receivedQueue.html" name="messages_receivedQueue">You cannot use this method directly: messages.receivedQueue</a>
    * <a href="https://docs.madelineproto.xyz/API_docs/methods/account_updatePasswordSettings.html" name="account_updatePasswordSettings">You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info): account.updatePasswordSettings</a>
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
* [`tests/testing.php`](https://github.com/danog/MadelineProto/blob/master/tests/testing.php) - examples for making/receiving calls, making secret chats, sending secret chat messages, videos, audios, voice recordings, gifs, stickers, photos, sending normal messages, videos, audios, voice recordings, gifs, stickers, photos.
* [`bot.php`](https://github.com/danog/MadelineProto/blob/master/bot.php) - examples for sending normal messages, downloading any media
* [`secret_bot.php`](https://github.com/danog/MadelineProto/blob/master/secret_bot.php) - secret chat bot
* [`magna.php`](https://github.com/danog/MadelineProto/blob/master/magna.php) - examples for receiving calls
* [`userbots/pipesbot.php`](https://github.com/danog/MadelineProto/blob/master/userbots/pipesbot.php) - examples for creating inline bots and using other inline bots via a userbot
* [`userbots/MadelineProto_bot.php`](https://github.com/danog/MadelineProto/blob/master/userbots/MadelineProto_bot.php) - Multi-function bot
* [`userbots/pwrtelegram_debug_bot`](https://github.com/danog/MadelineProto/blob/master/userbots/pwrtelegram_debug_bot.php) - Multi-function bot


