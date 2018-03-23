---
title: Methods
description: What do you want to do?
---
# What do you want to do?  
[Go back to API documentation index](..)  

[Go to the old code-version method index](api_README.md)  

* [Logout](https://docs.madelineproto.xyz/logout.html)

* [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)

* [Get all chats, broadcast a message to all chats](https://docs.madelineproto.xyz/docs/DIALOGS.html)

* [Get the full participant list of a channel/group/supergroup](https://docs.madelineproto.xyz/get_pwr_chat.html)

* [Get full info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_full_info.html)

* [Get info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_info.html)

* [Get info about the currently logged-in user](https://docs.madelineproto.xyz/get_self.html)

* [Upload or download files up to 1.5 GB](https://docs.madelineproto.xyz/docs/FILES.html)

* [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)

* [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)

***
<br><br>* <a href="account_changePhone.html" name="account_changePhone">Change the phone number associated to this account</a>  

* <a href="account_checkUsername.html" name="account_checkUsername">Check if this username is available</a>  

* <a href="account_confirmPhone.html" name="account_confirmPhone">Confirm this phone number is associated to this account, obtain phone_code_hash from sendConfirmPhoneCode</a>  

* <a href="account_deleteAccount.html" name="account_deleteAccount">Delete this account</a>  

* <a href="account_getAccountTTL.html" name="account_getAccountTTL">Get account TTL</a>  

* <a href="account_getAuthorizations.html" name="account_getAuthorizations">Get all logged-in authorizations</a>  

* <a href="account_getNotifySettings.html" name="account_getNotifySettings">Get notification settings</a>  

* <a href="account_getPassword.html" name="account_getPassword">Get the current password</a>  

* <a href="account_getPasswordSettings.html" name="account_getPasswordSettings">Get the current 2FA settings</a>  

* <a href="account_getPrivacy.html" name="account_getPrivacy">Get privacy settings</a>  

* <a href="account_getTmpPassword.html" name="account_getTmpPassword">Get temporary password for buying products through bots</a>  

* <a href="account_getWallPapers.html" name="account_getWallPapers">Returns a list of available wallpapers.</a>  

* <a href="account_registerDevice.html" name="account_registerDevice">Register device for push notifications</a>  

* <a href="account_reportPeer.html" name="account_reportPeer">Report for spam</a>  

* <a href="account_resetAuthorization.html" name="account_resetAuthorization">Delete a certain session</a>  

* <a href="account_resetNotifySettings.html" name="account_resetNotifySettings">Reset all notification settings</a>  

* <a href="account_sendChangePhoneCode.html" name="account_sendChangePhoneCode">Change the phone number</a>  

* <a href="account_sendConfirmPhoneCode.html" name="account_sendConfirmPhoneCode">Send confirmation phone code</a>  

* <a href="account_setAccountTTL.html" name="account_setAccountTTL">Set account TTL</a>  

* <a href="account_setPrivacy.html" name="account_setPrivacy">Set privacy settings</a>  

* <a href="account_unregisterDevice.html" name="account_unregisterDevice">Stop sending PUSH notifications to app</a>  

* <a href="account_updateDeviceLocked.html" name="account_updateDeviceLocked">Disable all notifications for a certain period</a>  

* <a href="account_updateNotifySettings.html" name="account_updateNotifySettings">Change notification settings</a>  

* <a href="account_updatePasswordSettings.html" name="account_updatePasswordSettings">Update the 2FA password settings</a>  

* <a href="account_updateProfile.html" name="account_updateProfile">Update profile info</a>  

* <a href="account_updateStatus.html" name="account_updateStatus">Update online status</a>  

* <a href="account_updateUsername.html" name="account_updateUsername">Update this user's username</a>  

***
<br><br>* <a href="auth_bindTempAuthKey.html" name="auth_bindTempAuthKey">You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info</a>  

* <a href="auth_cancelCode.html" name="auth_cancelCode">Invalidate sent phone code</a>  

* <a href="auth_checkPassword.html" name="auth_checkPassword">You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="auth_checkPhone.html" name="auth_checkPhone">Check if this phone number is registered on telegram</a>  

* <a href="auth_dropTempAuthKeys.html" name="auth_dropTempAuthKeys">Delete all temporary authorization keys except the ones provided</a>  

* <a href="auth_exportAuthorization.html" name="auth_exportAuthorization">You cannot use this method directly, use $MadelineProto->export_authorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html</a>  

* <a href="auth_importAuthorization.html" name="auth_importAuthorization">You cannot use this method directly, use $MadelineProto->import_authorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html</a>  

* <a href="auth_importBotAuthorization.html" name="auth_importBotAuthorization">You cannot use this method directly, use the bot_login method instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="auth_logOut.html" name="auth_logOut">You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="auth_recoverPassword.html" name="auth_recoverPassword">Use the code that was emailed to you after running $MadelineProto->auth->requestPasswordRecovery to login to your account</a>  

* <a href="auth_requestPasswordRecovery.html" name="auth_requestPasswordRecovery">Send an email to recover the 2FA password</a>  

* <a href="auth_resendCode.html" name="auth_resendCode">Resend the SMS verification code</a>  

* <a href="auth_resetAuthorizations.html" name="auth_resetAuthorizations">Delete all logged-in sessions.</a>  

* <a href="auth_sendCode.html" name="auth_sendCode">Use phone_login instead</a>  

* <a href="auth_sendInvites.html" name="auth_sendInvites">Invite friends to telegram!</a>  

* <a href="auth_signIn.html" name="auth_signIn">You cannot use this method directly, use the complete_phone_login method instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="auth_signUp.html" name="auth_signUp">You cannot use this method directly, use the complete_signup method instead (see https://docs.madelineproto.xyz for more info)</a>  

***
<br><br>* <a href="bots_answerWebhookJSONQuery.html" name="bots_answerWebhookJSONQuery">Send webhook request via bot API</a>  

* <a href="bots_sendCustomRequest.html" name="bots_sendCustomRequest">Send a custom request to the bot API</a>  

***
<br><br>* <a href="channels_checkUsername.html" name="channels_checkUsername">Check if this username is free and can be assigned to a channel/supergroup</a>  

* <a href="channels_createChannel.html" name="channels_createChannel">Create channel/supergroup</a>  

* <a href="channels_deleteChannel.html" name="channels_deleteChannel">Delete a channel/supergroup</a>  

* <a href="channels_deleteHistory.html" name="channels_deleteHistory">Delete the history of a supergroup/channel</a>  

* <a href="channels_deleteMessages.html" name="channels_deleteMessages">Delete channel/supergroup messages</a>  

* <a href="channels_deleteUserHistory.html" name="channels_deleteUserHistory">Delete all messages of a user in a channel/supergroup</a>  

* <a href="channels_editAbout.html" name="channels_editAbout">Edit the about text of a channel/supergroup</a>  

* <a href="channels_editAdmin.html" name="channels_editAdmin">Edit admin permissions of a user in a channel/supergroup</a>  

* <a href="channels_editBanned.html" name="channels_editBanned">Kick or ban a user from a channel/supergroup</a>  

* <a href="channels_editPhoto.html" name="channels_editPhoto">Edit the photo of a supergroup/channel</a>  

* <a href="channels_editTitle.html" name="channels_editTitle">Edit the title of a supergroup/channel</a>  

* <a href="channels_exportInvite.html" name="channels_exportInvite">Export the invite link of a channel</a>  

* <a href="channels_exportMessageLink.html" name="channels_exportMessageLink">Get the link of a message in a channel</a>  

* <a href="channels_getAdminLog.html" name="channels_getAdminLog">Get admin log of a channel/supergroup</a>  

* <a href="channels_getAdminedPublicChannels.html" name="channels_getAdminedPublicChannels">Get all supergroups/channels where you're admin</a>  

* <a href="channels_getChannels.html" name="channels_getChannels">Get info about multiple channels/supergroups</a>  

* <a href="channels_getFullChannel.html" name="channels_getFullChannel">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="channels_getMessages.html" name="channels_getMessages">Get channel/supergroup messages</a>  

* <a href="channels_getParticipant.html" name="channels_getParticipant">Get info about a certain channel/supergroup participant</a>  

* <a href="channels_getParticipants.html" name="channels_getParticipants">Get channel/supergroup participants (you should use `$MadelineProto->get_pwr_chat($id)` instead)</a>  

* <a href="channels_inviteToChannel.html" name="channels_inviteToChannel">Add users to channel/supergroup</a>  

* <a href="channels_joinChannel.html" name="channels_joinChannel">Join a channel/supergroup</a>  

* <a href="channels_leaveChannel.html" name="channels_leaveChannel">Leave a channel/supergroup</a>  

* <a href="channels_readHistory.html" name="channels_readHistory">Mark channel/supergroup history as read</a>  

* <a href="channels_readMessageContents.html" name="channels_readMessageContents">Mark channel/supergroup messages as read</a>  

* <a href="channels_reportSpam.html" name="channels_reportSpam">Report a supergroup/channel for spam</a>  

* <a href="channels_setStickers.html" name="channels_setStickers">Set the supergroup/channel stickerpack</a>  

* <a href="channels_toggleInvites.html" name="channels_toggleInvites">Allow or disallow any user to invite users to this channel/supergroup</a>  

* <a href="channels_togglePreHistoryHidden.html" name="channels_togglePreHistoryHidden">Enable or disable hidden history for new channel/supergroup users</a>  

* <a href="channels_toggleSignatures.html" name="channels_toggleSignatures">Toggle channel signatures</a>  

* <a href="channels_updatePinnedMessage.html" name="channels_updatePinnedMessage">Set the pinned message of a channel/supergroup</a>  

* <a href="channels_updateUsername.html" name="channels_updateUsername">Update the username of a supergroup/channel</a>  

***
<br><br>* <a href="contacts_block.html" name="contacts_block">Block a user</a>  

* <a href="contacts_deleteContact.html" name="contacts_deleteContact">Delete a contact</a>  

* <a href="contacts_deleteContacts.html" name="contacts_deleteContacts">Delete multiple contacts</a>  

* <a href="contacts_exportCard.html" name="contacts_exportCard">Export contact as card</a>  

* <a href="contacts_getBlocked.html" name="contacts_getBlocked">Get blocked users</a>  

* <a href="contacts_getContacts.html" name="contacts_getContacts">Get info about a certain contact</a>  

* <a href="contacts_getStatuses.html" name="contacts_getStatuses">Get online status of all users</a>  

* <a href="contacts_getTopPeers.html" name="contacts_getTopPeers">Get most used chats</a>  

* <a href="contacts_importCard.html" name="contacts_importCard">Import card as contact</a>  

* <a href="contacts_importContacts.html" name="contacts_importContacts">Add phone number as contact</a>  

* <a href="contacts_resetSaved.html" name="contacts_resetSaved">Reset saved contacts</a>  

* <a href="contacts_resetTopPeerRating.html" name="contacts_resetTopPeerRating">Reset top peer rating for a certain category/peer</a>  

* <a href="contacts_resolveUsername.html" name="contacts_resolveUsername">You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="contacts_search.html" name="contacts_search">Search contacts</a>  

* <a href="contacts_unblock.html" name="contacts_unblock">Unblock a user</a>  

***
<br><br>* <a href="help_getAppChangelog.html" name="help_getAppChangelog">Get the changelog of this app</a>  

* <a href="help_getAppUpdate.html" name="help_getAppUpdate">Get info about app updates</a>  

* <a href="help_getCdnConfig.html" name="help_getCdnConfig">Get CDN configuration</a>  

* <a href="help_getConfig.html" name="help_getConfig">Get server configuration</a>  

* <a href="help_getInviteText.html" name="help_getInviteText">Get invitation text</a>  

* <a href="help_getNearestDc.html" name="help_getNearestDc">Get nearest datacenter</a>  

* <a href="help_getRecentMeUrls.html" name="help_getRecentMeUrls">Get recent t.me URLs</a>  

* <a href="help_getSupport.html" name="help_getSupport">Get info of support user</a>  

* <a href="help_getTermsOfService.html" name="help_getTermsOfService">Get terms of service</a>  

* <a href="help_saveAppLog.html" name="help_saveAppLog">Log data for developer of this app</a>  

* <a href="help_setBotUpdatesStatus.html" name="help_setBotUpdatesStatus">Set the update status of webhook</a>  

***
<br><br>* <a href="initConnection.html" name="initConnection">Initializes connection and save information on the user's device and application.</a>  

***
<br><br>* <a href="invokeAfterMsg.html" name="invokeAfterMsg">Invokes a query after successfull completion of one of the previous queries.</a>  

***
<br><br>* <a href="invokeAfterMsgs.html" name="invokeAfterMsgs">Result type returned by a current query.</a>  

***
<br><br>* <a href="invokeWithLayer.html" name="invokeWithLayer">Invoke this method with layer X</a>  

***
<br><br>* <a href="invokeWithoutUpdates.html" name="invokeWithoutUpdates">Invoke with method without returning updates in the socket</a>  

***
<br><br>* <a href="langpack_getDifference.html" name="langpack_getDifference">Get language pack updates</a>  

* <a href="langpack_getLangPack.html" name="langpack_getLangPack">Get language pack</a>  

* <a href="langpack_getLanguages.html" name="langpack_getLanguages">Get available languages</a>  

* <a href="langpack_getStrings.html" name="langpack_getStrings">Get language pack strings</a>  

***
<br><br>* <a href="messages_acceptEncryption.html" name="messages_acceptEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats</a>  

* <a href="messages_addChatUser.html" name="messages_addChatUser">Add a user to a normal chat (use channels->inviteToChannel for supergroups)</a>  

* <a href="messages_checkChatInvite.html" name="messages_checkChatInvite">Check if an invitation link is valid</a>  

* <a href="messages_clearRecentStickers.html" name="messages_clearRecentStickers">Clear all recent stickers</a>  

* <a href="messages_createChat.html" name="messages_createChat">Create a chat (not supergroup)</a>  

* <a href="messages_deleteChatUser.html" name="messages_deleteChatUser">Delete a user from a chat (not supergroup)</a>  

* <a href="messages_deleteHistory.html" name="messages_deleteHistory">Delete chat history</a>  

* <a href="messages_deleteMessages.html" name="messages_deleteMessages">Delete messages</a>  

* <a href="messages_discardEncryption.html" name="messages_discardEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats</a>  

* <a href="messages_editChatAdmin.html" name="messages_editChatAdmin">Edit admin permissions</a>  

* <a href="messages_editChatPhoto.html" name="messages_editChatPhoto">Edit the photo of a normal chat (not supergroup)</a>  

* <a href="messages_editChatTitle.html" name="messages_editChatTitle">Edit the title of a normal chat (not supergroup)</a>  

* <a href="messages_editInlineBotMessage.html" name="messages_editInlineBotMessage">Edit a sent inline message</a>  

* <a href="messages_editMessage.html" name="messages_editMessage">Edit a message</a>  

* <a href="messages_exportChatInvite.html" name="messages_exportChatInvite">Export chat invite </a>  

* <a href="messages_faveSticker.html" name="messages_faveSticker">Add a sticker to favorites</a>  

* <a href="messages_forwardMessage.html" name="messages_forwardMessage">Forward message</a>  

* <a href="messages_forwardMessages.html" name="messages_forwardMessages">Forward messages</a>  

* <a href="messages_getAllChats.html" name="messages_getAllChats">Get all chats (not supergroups or channels)</a>  

* <a href="messages_getAllDrafts.html" name="messages_getAllDrafts">Get all message drafts</a>  

* <a href="messages_getAllStickers.html" name="messages_getAllStickers">Get all stickerpacks</a>  

* <a href="messages_getArchivedStickers.html" name="messages_getArchivedStickers">Get all archived stickers</a>  

* <a href="messages_getAttachedStickers.html" name="messages_getAttachedStickers">Get stickers attachable to images</a>  

* <a href="messages_getBotCallbackAnswer.html" name="messages_getBotCallbackAnswer">Get the callback answer of a bot (after clicking a button)</a>  

* <a href="messages_getChats.html" name="messages_getChats">Get info about chats</a>  

* <a href="messages_getCommonChats.html" name="messages_getCommonChats">Get chats in common with a user</a>  

* <a href="messages_getDhConfig.html" name="messages_getDhConfig">You cannot use this method directly, instead use $MadelineProto->get_dh_config();</a>  

* <a href="messages_getDialogs.html" name="messages_getDialogs">Gets list of chats: you should use $MadelineProto->get_dialogs() instead: https://docs.madelineproto.xyz/docs/DIALOGS.html</a>  

* <a href="messages_getDocumentByHash.html" name="messages_getDocumentByHash">Get document by SHA256 hash</a>  

* <a href="messages_getFavedStickers.html" name="messages_getFavedStickers">Get favorite stickers</a>  

* <a href="messages_getFeaturedStickers.html" name="messages_getFeaturedStickers">Get featured stickers</a>  

* <a href="messages_getFullChat.html" name="messages_getFullChat">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="messages_getGameHighScores.html" name="messages_getGameHighScores">Get high scores of a game</a>  

* <a href="messages_getHistory.html" name="messages_getHistory">Get previous messages of a group</a>  

* <a href="messages_getInlineBotResults.html" name="messages_getInlineBotResults">Call inline bot</a>  

* <a href="messages_getInlineGameHighScores.html" name="messages_getInlineGameHighScores">Get high scores of a game sent in an inline message</a>  

* <a href="messages_getMaskStickers.html" name="messages_getMaskStickers">Get masks</a>  

* <a href="messages_getMessageEditData.html" name="messages_getMessageEditData">Check if about to edit a message or a media caption</a>  

* <a href="messages_getMessages.html" name="messages_getMessages">Get messages</a>  

* <a href="messages_getMessagesViews.html" name="messages_getMessagesViews">Get and increase message views</a>  

* <a href="messages_getPeerDialogs.html" name="messages_getPeerDialogs">Get dialog info of peers</a>  

* <a href="messages_getPeerSettings.html" name="messages_getPeerSettings">Get the settings of  apeer</a>  

* <a href="messages_getPinnedDialogs.html" name="messages_getPinnedDialogs">Get pinned dialogs</a>  

* <a href="messages_getRecentLocations.html" name="messages_getRecentLocations">Get recent locations</a>  

* <a href="messages_getRecentStickers.html" name="messages_getRecentStickers">Get recent stickers</a>  

* <a href="messages_getSavedGifs.html" name="messages_getSavedGifs">Get saved gifs</a>  

* <a href="messages_getStickerSet.html" name="messages_getStickerSet">Get a stickerset</a>  

* <a href="messages_getUnreadMentions.html" name="messages_getUnreadMentions">Get unread mentions</a>  

* <a href="messages_getWebPage.html" name="messages_getWebPage">Get webpage preview</a>  

* <a href="messages_getWebPagePreview.html" name="messages_getWebPagePreview">Get webpage preview</a>  

* <a href="messages_hideReportSpam.html" name="messages_hideReportSpam">Hide report spam popup</a>  

* <a href="messages_importChatInvite.html" name="messages_importChatInvite">Import chat invite</a>  

* <a href="messages_installStickerSet.html" name="messages_installStickerSet">Add a sticker set</a>  

* <a href="messages_migrateChat.html" name="messages_migrateChat">Convert chat to supergroup</a>  

* <a href="messages_readEncryptedHistory.html" name="messages_readEncryptedHistory">Mark messages as read in secret chats</a>  

* <a href="messages_readFeaturedStickers.html" name="messages_readFeaturedStickers">Mark new featured stickers as read</a>  

* <a href="messages_readHistory.html" name="messages_readHistory">Mark messages as read</a>  

* <a href="messages_readMentions.html" name="messages_readMentions">Mark mentions as read</a>  

* <a href="messages_readMessageContents.html" name="messages_readMessageContents">Mark message as read</a>  

* <a href="messages_receivedMessages.html" name="messages_receivedMessages">Mark messages as read</a>  

* <a href="messages_receivedQueue.html" name="messages_receivedQueue">You cannot use this method directly</a>  

* <a href="messages_reorderPinnedDialogs.html" name="messages_reorderPinnedDialogs">Reorder pinned dialogs</a>  

* <a href="messages_reorderStickerSets.html" name="messages_reorderStickerSets">Reorder sticker sets</a>  

* <a href="messages_reportEncryptedSpam.html" name="messages_reportEncryptedSpam">Report for spam a secret chat</a>  

* <a href="messages_reportSpam.html" name="messages_reportSpam">Report a peer for spam</a>  

* <a href="messages_requestEncryption.html" name="messages_requestEncryption">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats</a>  

* <a href="messages_saveDraft.html" name="messages_saveDraft">Save a message draft</a>  

* <a href="messages_saveGif.html" name="messages_saveGif">Save a GIF</a>  

* <a href="messages_saveRecentSticker.html" name="messages_saveRecentSticker">Add a sticker to recent stickers</a>  

* <a href="messages_search.html" name="messages_search">Search peers or messages</a>  

* <a href="messages_searchGifs.html" name="messages_searchGifs">Search gifs</a>  

* <a href="messages_searchGlobal.html" name="messages_searchGlobal">Global message search</a>  

* <a href="messages_sendEncrypted.html" name="messages_sendEncrypted">Send message to secret chat</a>  

* <a href="messages_sendEncryptedFile.html" name="messages_sendEncryptedFile">Send a file to a secret chat</a>  

* <a href="messages_sendEncryptedService.html" name="messages_sendEncryptedService">Send a service message to a secret chat</a>  

* <a href="messages_sendInlineBotResult.html" name="messages_sendInlineBotResult">Send a received bot result to the chat</a>  

* <a href="messages_sendMedia.html" name="messages_sendMedia">Send a media</a>  

* <a href="messages_sendMessage.html" name="messages_sendMessage">Send a message</a>  

* <a href="messages_sendMultiMedia.html" name="messages_sendMultiMedia">Send an album</a>  

* <a href="messages_sendScreenshotNotification.html" name="messages_sendScreenshotNotification">Send screenshot notification</a>  

* <a href="messages_setBotCallbackAnswer.html" name="messages_setBotCallbackAnswer">Bots only: set the callback answer (after a button was clicked)</a>  

* <a href="messages_setBotPrecheckoutResults.html" name="messages_setBotPrecheckoutResults">Bots only: set precheckout results</a>  

* <a href="messages_setBotShippingResults.html" name="messages_setBotShippingResults">Bots only: set shipping results</a>  

* <a href="messages_setEncryptedTyping.html" name="messages_setEncryptedTyping">Send typing notification to secret chat</a>  

* <a href="messages_setGameScore.html" name="messages_setGameScore">Set the game score</a>  

* <a href="messages_setInlineBotResults.html" name="messages_setInlineBotResults">Bots only: set the results of an inline query</a>  

* <a href="messages_setInlineGameScore.html" name="messages_setInlineGameScore">Set the game score of an inline message</a>  

* <a href="messages_setTyping.html" name="messages_setTyping">Change typing status</a>  

* <a href="messages_startBot.html" name="messages_startBot">Start a bot</a>  

* <a href="messages_toggleChatAdmins.html" name="messages_toggleChatAdmins">Enable all users are admins in normal groups (not supergroups)</a>  

* <a href="messages_toggleDialogPin.html" name="messages_toggleDialogPin">Pin or unpin dialog</a>  

* <a href="messages_uninstallStickerSet.html" name="messages_uninstallStickerSet">Remove a sticker set</a>  

* <a href="messages_uploadEncryptedFile.html" name="messages_uploadEncryptedFile">Upload a secret chat file without sending it to anyone</a>  

* <a href="messages_uploadMedia.html" name="messages_uploadMedia">Upload a file without sending it to anyone</a>  

***
<br><br>* <a href="payments_clearSavedInfo.html" name="payments_clearSavedInfo">Clear saved payments info</a>  

* <a href="payments_getPaymentForm.html" name="payments_getPaymentForm">Get payment form</a>  

* <a href="payments_getPaymentReceipt.html" name="payments_getPaymentReceipt">Get payment receipt</a>  

* <a href="payments_getSavedInfo.html" name="payments_getSavedInfo">Get saved payments info</a>  

* <a href="payments_sendPaymentForm.html" name="payments_sendPaymentForm">Bots only: send payment form</a>  

* <a href="payments_validateRequestedInfo.html" name="payments_validateRequestedInfo">Validate requested payment info</a>  

***
<br><br>* <a href="phone_acceptCall.html" name="phone_acceptCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls</a>  

* <a href="phone_confirmCall.html" name="phone_confirmCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls</a>  

* <a href="phone_discardCall.html" name="phone_discardCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls</a>  

* <a href="phone_getCallConfig.html" name="phone_getCallConfig">Get call configuration</a>  

* <a href="phone_receivedCall.html" name="phone_receivedCall">Notify server that you received a call (server will refuse all incoming calls until the current call is over)</a>  

* <a href="phone_requestCall.html" name="phone_requestCall">You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls</a>  

* <a href="phone_saveCallDebug.html" name="phone_saveCallDebug">Save call debugging info</a>  

* <a href="phone_setCallRating.html" name="phone_setCallRating">Set phone call rating</a>  

***
<br><br>* <a href="photos_deletePhotos.html" name="photos_deletePhotos">Delete profile photos</a>  

* <a href="photos_getUserPhotos.html" name="photos_getUserPhotos">Get the profile photos of a user</a>  

* <a href="photos_updateProfilePhoto.html" name="photos_updateProfilePhoto">Update the profile photo (use photos->uploadProfilePhoto to upload the photo)</a>  

* <a href="photos_uploadProfilePhoto.html" name="photos_uploadProfilePhoto">Upload profile photo</a>  

***
<br><br>* <a href="stickers_addStickerToSet.html" name="stickers_addStickerToSet">Add sticker to stickerset</a>  

* <a href="stickers_changeStickerPosition.html" name="stickers_changeStickerPosition">Change sticker position in photo</a>  

* <a href="stickers_createStickerSet.html" name="stickers_createStickerSet">Create stickerset</a>  

* <a href="stickers_removeStickerFromSet.html" name="stickers_removeStickerFromSet">Remove sticker from stickerset</a>  

***
<br><br>* <a href="updates_getChannelDifference.html" name="updates_getChannelDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates</a>  

* <a href="updates_getDifference.html" name="updates_getDifference">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates</a>  

* <a href="updates_getState.html" name="updates_getState">You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates</a>  

***
<br><br>* <a href="upload_getCdnFile.html" name="upload_getCdnFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

* <a href="upload_getCdnFileHashes.html" name="upload_getCdnFileHashes">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

* <a href="upload_getFile.html" name="upload_getFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

* <a href="upload_getWebFile.html" name="upload_getWebFile">Download a file through telegram</a>  

* <a href="upload_reuploadCdnFile.html" name="upload_reuploadCdnFile">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

* <a href="upload_saveBigFilePart.html" name="upload_saveBigFilePart">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

* <a href="upload_saveFilePart.html" name="upload_saveFilePart">You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info</a>  

***
<br><br>* <a href="users_getFullUser.html" name="users_getFullUser">You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)</a>  

* <a href="users_getUsers.html" name="users_getUsers">Get info about users</a>  

