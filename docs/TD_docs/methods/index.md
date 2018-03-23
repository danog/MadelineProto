---
title: Methods
description: What do you want to do?
---
# What do you want to do?  
[Go back to API documentation index](..)  

[Go to the old code-version method index](api_index.md)  

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

* <a href="acceptCall.html" name="acceptCall">Accepts incoming call</a>  

* <a href="addChatMember.html" name="addChatMember">Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server</a>  

* <a href="addChatMembers.html" name="addChatMembers">Adds many new members to the chat. Currently, available only for channels. Can't be used to join the channel. Members can't be added to broadcast channel if it has more than 200 members. Members will not be added until chat state will be synchronized with the server</a>  

* <a href="addFavoriteSticker.html" name="addFavoriteSticker">Adds new sticker to the list of favorite stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list. Only stickers belonging to a sticker set can be added to the list</a>  

* <a href="addNetworkStatistics.html" name="addNetworkStatistics">Adds specified data to data usage statistics. Can be called before authorization</a>  

* <a href="addRecentSticker.html" name="addRecentSticker">Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list. Only stickers belonging to a sticker set can be added to the list</a>  

* <a href="addRecentlyFoundChat.html" name="addRecentlyFoundChat">Adds chat to the list of recently found chats. The chat is added to the beginning of the list. If the chat is already in the list, at first it is removed from the list</a>  

* <a href="addSavedAnimation.html" name="addSavedAnimation">Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list</a>  

* <a href="addStickerToSet.html" name="addStickerToSet">Bots only. Adds new sticker to a set. Returns the sticker set</a>  

* <a href="answerCallbackQuery.html" name="answerCallbackQuery">Bots only. Sets result of a callback query</a>  

* <a href="answerCustomQuery.html" name="answerCustomQuery">Bots only. Answers a custom query</a>  

* <a href="answerInlineQuery.html" name="answerInlineQuery">Bots only. Sets result of an inline query</a>  

* <a href="answerPreCheckoutQuery.html" name="answerPreCheckoutQuery">Bots only. Sets result of a pre checkout query</a>  

* <a href="answerShippingQuery.html" name="answerShippingQuery">Bots only. Sets result of a shipping query</a>  

* <a href="blockUser.html" name="blockUser">Adds user to black list</a>  

* <a href="cancelDownloadFile.html" name="cancelDownloadFile">Stops file downloading. If file is already downloaded, does nothing</a>  

* <a href="cancelUploadFile.html" name="cancelUploadFile">Stops file uploading. Works only for files uploaded using uploadFile. For other files the behavior is undefined</a>  

* <a href="changeAbout.html" name="changeAbout">Changes about information of logged in user</a>  

* <a href="changeAccountTtl.html" name="changeAccountTtl">Changes period of inactivity, after which the account of currently logged in user will be automatically deleted</a>  

* <a href="changeChannelDescription.html" name="changeChannelDescription">Changes information about the channel. Needs appropriate rights in the channel</a>  

* <a href="changeChannelUsername.html" name="changeChannelUsername">Changes username of the channel. Needs creator privileges in the channel</a>  

* <a href="changeChatDraftMessage.html" name="changeChatDraftMessage">Changes chat draft message</a>  

* <a href="changeChatMemberStatus.html" name="changeChatMemberStatus">Changes status of the chat member, need appropriate privileges. This function is currently not suitable for adding new members to the chat, use addChatMember instead. Status will not be changed until chat state will be synchronized with the server</a>  

* <a href="changeChatPhoto.html" name="changeChatPhoto">Changes chat photo. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Photo will not change before request to the server completes</a>  

* <a href="changeChatReportSpamState.html" name="changeChatReportSpamState">Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever</a>  

* <a href="changeChatTitle.html" name="changeChatTitle">Changes chat title. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Title will not change before request to the server completes</a>  

* <a href="changeName.html" name="changeName">Changes first and last names of logged in user. If something changes, updateUser will be sent</a>  

* <a href="changePhoneNumber.html" name="changePhoneNumber">Changes user's phone number and sends authentication code to the new user's phone number. Returns authStateWaitCode with information about sent code on success</a>  

* <a href="changeStickerSet.html" name="changeStickerSet">Installs/uninstalls or enables/archives sticker set</a>  

* <a href="changeUsername.html" name="changeUsername">Changes username of logged in user. If something changes, updateUser will be sent</a>  

* <a href="checkAuthBotToken.html" name="checkAuthBotToken">Check bot's authentication token to log in as a bot. Works only when getAuthState returns authStateWaitPhoneNumber. Can be used instead of setAuthPhoneNumber and checkAuthCode to log in. Returns authStateOk on success</a>  

* <a href="checkAuthCode.html" name="checkAuthCode">Checks authentication code. Works only when getAuthState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success</a>  

* <a href="checkAuthPassword.html" name="checkAuthPassword">Checks password for correctness. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success</a>  

* <a href="checkChangePhoneNumberCode.html" name="checkChangePhoneNumberCode">Checks authentication code sent to change user's phone number. Returns authStateOk on success</a>  

* <a href="checkChatInviteLink.html" name="checkChatInviteLink">Checks chat invite link for validness and returns information about the corresponding chat</a>  

* <a href="clearRecentStickers.html" name="clearRecentStickers">Clears list of recently used stickers</a>  

* <a href="closeChat.html" name="closeChat">Chat is closed by the user. Many useful activities depends on chat being opened or closed.</a>  

* <a href="closeSecretChat.html" name="closeSecretChat">Closes secret chat, effectively transfering its state to "Closed"</a>  

* <a href="createCall.html" name="createCall">Creates new call</a>  

* <a href="createChannelChat.html" name="createChannelChat">Returns existing chat corresponding to the known channel</a>  

* <a href="createGroupChat.html" name="createGroupChat">Returns existing chat corresponding to the known group</a>  

* <a href="createNewChannelChat.html" name="createNewChannelChat">Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat</a>  

* <a href="createNewGroupChat.html" name="createNewGroupChat">Creates new group chat and send corresponding messageGroupChatCreate, returns created chat</a>  

* <a href="createNewSecretChat.html" name="createNewSecretChat">Creates new secret chat, returns created chat</a>  

* <a href="createNewStickerSet.html" name="createNewStickerSet">Bots only. Creates new sticker set. Returns created sticker set</a>  

* <a href="createPrivateChat.html" name="createPrivateChat">Returns existing chat corresponding to the given user</a>  

* <a href="createSecretChat.html" name="createSecretChat">Returns existing chat corresponding to the known secret chat</a>  

* <a href="createTemporaryPassword.html" name="createTemporaryPassword">Creates new temporary password for payments processing</a>  

* <a href="debugCall.html" name="debugCall">Sends call debug information</a>  

* <a href="deleteAccount.html" name="deleteAccount">Deletes the account of currently logged in user, deleting from the server all information associated with it. Account's phone number can be used to create new account, but only once in two weeks</a>  

* <a href="deleteChannel.html" name="deleteChannel">Deletes channel along with all messages in corresponding chat. Releases channel username and removes all members. Needs creator privileges in the channel. Channels with more than 1000 members can't be deleted</a>  

* <a href="deleteChatHistory.html" name="deleteChatHistory">Deletes all messages in the chat. Can't be used for channel chats</a>  

* <a href="deleteChatReplyMarkup.html" name="deleteChatReplyMarkup">Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed</a>  

* <a href="deleteContacts.html" name="deleteContacts">Deletes users from contacts list</a>  

* <a href="deleteFavoriteSticker.html" name="deleteFavoriteSticker">Removes a sticker from the list of favorite stickers</a>  

* <a href="deleteFile.html" name="deleteFile">Deletes a file from TDLib file cache</a>  

* <a href="deleteImportedContacts.html" name="deleteImportedContacts">Deletes all imported contacts</a>  

* <a href="deleteMessages.html" name="deleteMessages">Deletes messages</a>  

* <a href="deleteMessagesFromUser.html" name="deleteMessagesFromUser">Deletes all messages in the chat sent by the specified user. Works only in supergroup channel chats, needs can_delete_messages administrator privileges</a>  

* <a href="deleteProfilePhoto.html" name="deleteProfilePhoto">Deletes profile photo. If something changes, updateUser will be sent</a>  

* <a href="deleteRecentHashtag.html" name="deleteRecentHashtag">Deletes a hashtag from the list of recently used hashtags</a>  

* <a href="deleteRecentSticker.html" name="deleteRecentSticker">Removes a sticker from the list of recently used stickers</a>  

* <a href="deleteRecentlyFoundChat.html" name="deleteRecentlyFoundChat">Deletes chat from the list of recently found chats</a>  

* <a href="deleteRecentlyFoundChats.html" name="deleteRecentlyFoundChats">Clears list of recently found chats</a>  

* <a href="deleteSavedAnimation.html" name="deleteSavedAnimation">Removes an animation from the list of saved animations</a>  

* <a href="deleteSavedCredentials.html" name="deleteSavedCredentials">Deletes saved credentials for all payments provider bots</a>  

* <a href="deleteSavedOrderInfo.html" name="deleteSavedOrderInfo">Deletes saved order info</a>  

* <a href="deleteStickerFromSet.html" name="deleteStickerFromSet">Bots only. Deletes a sticker from the set it belongs to. Sticker set should be created by the bot</a>  

* <a href="deleteTopChat.html" name="deleteTopChat">Delete a chat from a list of frequently used chats. Supported only if chat info database is enabled</a>  

* <a href="discardCall.html" name="discardCall">Discards a call</a>  

* <a href="downloadFile.html" name="downloadFile">Asynchronously downloads file from cloud. Updates updateFile will notify about download progress and successful download</a>  

* <a href="editInlineMessageCaption.html" name="editInlineMessageCaption">Bots only. Edits caption of an inline message content sent via bot</a>  

* <a href="editInlineMessageReplyMarkup.html" name="editInlineMessageReplyMarkup">Bots only. Edits reply markup of an inline message sent via bot</a>  

* <a href="editInlineMessageText.html" name="editInlineMessageText">Bots only. Edits text of an inline text or game message sent via bot</a>  

* <a href="editMessageCaption.html" name="editMessageCaption">Edits message content caption. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side</a>  

* <a href="editMessageReplyMarkup.html" name="editMessageReplyMarkup">Bots only. Edits message reply markup. Returns edited message after edit is complete server side</a>  

* <a href="editMessageText.html" name="editMessageText">Edits text of text or game message. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side</a>  

* <a href="exportChatInviteLink.html" name="exportChatInviteLink">Generates new chat invite link, previously generated link is revoked. Available for group and channel chats. In groups can be called only by creator, in channels requires appropriate rights</a>  

* <a href="finishFileGeneration.html" name="finishFileGeneration">Finishes file generation</a>  

* <a href="forwardMessages.html" name="forwardMessages">Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message</a>  

* <a href="getAccountTtl.html" name="getAccountTtl">Returns period of inactivity, after which the account of currently logged in user will be automatically deleted</a>  

* <a href="getActiveSessions.html" name="getActiveSessions">Returns all active sessions of logged in user</a>  

* <a href="getArchivedStickerSets.html" name="getArchivedStickerSets">Returns list of archived sticker sets</a>  

* <a href="getAttachedStickerSets.html" name="getAttachedStickerSets">Returns list of sticker sets attached to a file, currently only photos and videos can have attached sticker sets</a>  

* <a href="getAuthState.html" name="getAuthState">Returns current authorization state, offline request</a>  

* <a href="getBlockedUsers.html" name="getBlockedUsers">Returns users blocked by the current user</a>  

* <a href="getCallbackQueryAnswer.html" name="getCallbackQueryAnswer">Sends callback query to a bot and returns answer to it. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots</a>  

* <a href="getChannel.html" name="getChannel">Returns information about a channel by its identifier, offline request if current user is not a bot</a>  

* <a href="getChannelFull.html" name="getChannelFull">Returns full information about a channel by its identifier, cached for at most 1 minute</a>  

* <a href="getChannelMembers.html" name="getChannelMembers">Returns information about channel members or banned users. Can be used only if channel_full->can_get_members == true. Administrator privileges may be additionally needed for some filters</a>  

* <a href="getChat.html" name="getChat">Returns information about a chat by its identifier, offline request if current user is not a bot</a>  

* <a href="getChatEventLog.html" name="getChatEventLog">Returns list of service actions taken by chat members and administrators in the last 48 hours, available only in channels. Requires administrator rights. Returns result in reverse chronological order, i. e. in order of decreasing event_id</a>  

* <a href="getChatHistory.html" name="getChatHistory">Returns messages in a chat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id. Offline request if only_local is true</a>  

* <a href="getChatMember.html" name="getChatMember">Returns information about one participant of the chat</a>  

* <a href="getChatReportSpamState.html" name="getChatReportSpamState">Returns current chat report spam state</a>  

* <a href="getChats.html" name="getChats">Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1</a>  

* <a href="getCommonChats.html" name="getCommonChats">Returns list of common chats with an other given user. Chats are sorted by their type and creation date</a>  

* <a href="getCreatedPublicChats.html" name="getCreatedPublicChats">Returns list of created public chats</a>  

* <a href="getFavoriteStickers.html" name="getFavoriteStickers">Returns favorite stickers</a>  

* <a href="getFile.html" name="getFile">Returns information about a file, offline request</a>  

* <a href="getFileExtension.html" name="getFileExtension">Returns file's extension guessing only by its mime type. Returns empty string on failure. Offline method. Can be called before authorization. Can be called synchronously</a>  

* <a href="getFileMimeType.html" name="getFileMimeType">Returns file's mime type guessing only by its extension. Returns empty string on failure. Offline method. Can be called before authorization. Can be called synchronously</a>  

* <a href="getFilePersistent.html" name="getFilePersistent">Returns information about a file by its persistent id, offline request. May be used to register a URL as a file for further uploading or sending as message</a>  

* <a href="getGameHighScores.html" name="getGameHighScores">Bots only. Returns game high scores and some part of the score table around of the specified user in the game</a>  

* <a href="getGroup.html" name="getGroup">Returns information about a group by its identifier, offline request if current user is not a bot</a>  

* <a href="getGroupFull.html" name="getGroupFull">Returns full information about a group by its identifier</a>  

* <a href="getImportedContactCount.html" name="getImportedContactCount">Returns total number of imported contacts</a>  

* <a href="getInlineGameHighScores.html" name="getInlineGameHighScores">Bots only. Returns game high scores and some part of the score table around of the specified user in the game</a>  

* <a href="getInlineQueryResults.html" name="getInlineQueryResults">Sends inline query to a bot and returns its results. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots</a>  

* <a href="getInstalledStickerSets.html" name="getInstalledStickerSets">Returns list of installed sticker sets</a>  

* <a href="getInviteText.html" name="getInviteText">Returns invite text for invitation of new users</a>  

* <a href="getMe.html" name="getMe">Returns current logged in user</a>  

* <a href="getMessage.html" name="getMessage">Returns information about a message</a>  

* <a href="getMessages.html" name="getMessages">Returns information about messages. If message is not found, returns null on the corresponding position of the result</a>  

* <a href="getNetworkStatistics.html" name="getNetworkStatistics">Returns network data usage statistics. Can be called before authorization</a>  

* <a href="getNotificationSettings.html" name="getNotificationSettings">Returns notification settings for a given scope</a>  

* <a href="getOption.html" name="getOption">Returns value of an option by its name. See list of available options on https: core.telegram.org/tdlib/options. Can be called before authorization</a>  

* <a href="getPasswordState.html" name="getPasswordState">Returns current state of two-step verification</a>  

* <a href="getPaymentForm.html" name="getPaymentForm">Returns invoice payment form. The method should be called when user presses inlineKeyboardButtonBuy</a>  

* <a href="getPaymentReceipt.html" name="getPaymentReceipt">Returns information about successful payment</a>  

* <a href="getPrivacy.html" name="getPrivacy">Returns current privacy settings</a>  

* <a href="getProxy.html" name="getProxy">Returns current set up proxy. Can be called before authorization</a>  

* <a href="getPublicMessageLink.html" name="getPublicMessageLink">Returns public HTTPS link to a message. Available only for messages in public channels</a>  

* <a href="getRecentInlineBots.html" name="getRecentInlineBots">Returns up to 20 recently used inline bots in the order of the last usage</a>  

* <a href="getRecentStickers.html" name="getRecentStickers">Returns list of recently used stickers</a>  

* <a href="getRecoveryEmail.html" name="getRecoveryEmail">Returns set up recovery email. This method can be used to verify a password provided by the user</a>  

* <a href="getSavedAnimations.html" name="getSavedAnimations">Returns saved animations</a>  

* <a href="getSavedOrderInfo.html" name="getSavedOrderInfo">Returns saved order info if any</a>  

* <a href="getSecretChat.html" name="getSecretChat">Returns information about a secret chat by its identifier, offline request</a>  

* <a href="getStickerEmojis.html" name="getStickerEmojis">Returns emojis corresponding to a sticker</a>  

* <a href="getStickerSet.html" name="getStickerSet">Returns information about sticker set by its identifier</a>  

* <a href="getStickers.html" name="getStickers">Returns stickers from installed ordinary sticker sets corresponding to the given emoji. If emoji is not empty, elso favorite and recently used stickers may be returned</a>  

* <a href="getStorageStatistics.html" name="getStorageStatistics">Returns storage usage statistics</a>  

* <a href="getStorageStatisticsFast.html" name="getStorageStatisticsFast">Quickly returns approximate storage usage statistics</a>  

* <a href="getSupportUser.html" name="getSupportUser">Returns user that can be contacted to get support</a>  

* <a href="getTemporaryPasswordState.html" name="getTemporaryPasswordState">Returns information about current temporary password</a>  

* <a href="getTermsOfService.html" name="getTermsOfService">Returns terms of service. Can be called before authorization</a>  

* <a href="getTextEntities.html" name="getTextEntities">Returns all mentions, hashtags, bot commands, URLs and emails contained in the text. Offline method. Can be called before authorization. Can be called synchronously</a>  

* <a href="getTopChats.html" name="getTopChats">Returns a list of frequently used chats. Supported only if chat info database is enabled</a>  

* <a href="getTrendingStickerSets.html" name="getTrendingStickerSets">Returns list of trending sticker sets</a>  

* <a href="getUser.html" name="getUser">Returns information about a user by its identifier, offline request if current user is not a bot</a>  

* <a href="getUserFull.html" name="getUserFull">Returns full information about a user by its identifier</a>  

* <a href="getUserProfilePhotos.html" name="getUserProfilePhotos">Returns profile photos of the user. Result of this query may be outdated: some photos may be already deleted</a>  

* <a href="getWallpapers.html" name="getWallpapers">Returns background wallpapers</a>  

* <a href="getWebPageInstantView.html" name="getWebPageInstantView">Returns web page instant view if available. Returns error 404 if web page has no instant view</a>  

* <a href="getWebPagePreview.html" name="getWebPagePreview">Returns web page preview by text of the message. Do not call this function to often. Returns error 404 if web page has no preview</a>  

* <a href="importChatInviteLink.html" name="importChatInviteLink">Imports chat invite link, adds current user to a chat if possible. Member will not be added until chat state will be synchronized with the server</a>  

* <a href="importContacts.html" name="importContacts">Adds new contacts/edits existing contacts, contacts user identifiers are ignored</a>  

* <a href="migrateGroupChatToChannelChat.html" name="migrateGroupChatToChannelChat">Creates new channel supergroup chat from existing group chat and send corresponding messageChatMigrateTo and messageChatMigrateFrom. Deactivates group</a>  

* <a href="openChat.html" name="openChat">Chat is opened by the user. Many useful activities depends on chat being opened or closed. For example, in channels all updates are received only for opened chats</a>  

* <a href="openMessageContent.html" name="openMessageContent">Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message. You will receive updateOpenMessageContent if something has changed</a>  

* <a href="optimizeStorage.html" name="optimizeStorage">Optimizes storage usage, i.e. deletes some files and return new storage usage statistics. Secret thumbnails can't be deleted</a>  

* <a href="pinChannelMessage.html" name="pinChannelMessage">Pins a message in a supergroup channel chat. Needs appropriate rights in the channel</a>  

* <a href="processDcUpdate.html" name="processDcUpdate">Handles DC_UPDATE push service notification. Can be called before authorization</a>  

* <a href="rateCall.html" name="rateCall">Sends call rating</a>  

* <a href="recoverAuthPassword.html" name="recoverAuthPassword">Recovers password with recovery code sent to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success</a>  

* <a href="recoverPassword.html" name="recoverPassword">Recovers password with recovery code sent to email</a>  

* <a href="registerDevice.html" name="registerDevice">Registers current used device for receiving push notifications</a>  

* <a href="reorderInstalledStickerSets.html" name="reorderInstalledStickerSets">Changes the order of installed sticker sets</a>  

* <a href="reportChannelSpam.html" name="reportChannelSpam">Reports some supergroup channel messages from a user as spam messages</a>  

* <a href="reportChat.html" name="reportChat">Reports chat to Telegram moderators. Can be used only for a channel chat or a private chat with a bot, because all other chats can't be checked by moderators</a>  

* <a href="requestAuthPasswordRecovery.html" name="requestAuthPasswordRecovery">Requests to send password recovery code to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateWaitPassword on success</a>  

* <a href="requestPasswordRecovery.html" name="requestPasswordRecovery">Requests to send password recovery code to email</a>  

* <a href="resendAuthCode.html" name="resendAuthCode">Resends authentication code to the user. Works only when getAuthState returns authStateWaitCode and next_code_type of result is not null. Returns authStateWaitCode on success</a>  

* <a href="resendChangePhoneNumberCode.html" name="resendChangePhoneNumberCode">Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success</a>  

* <a href="resetAllNotificationSettings.html" name="resetAllNotificationSettings">Resets all notification settings to the default value. By default the only muted chats are supergroups, sound is set to 'default' and message previews are showed</a>  

* <a href="resetAuth.html" name="resetAuth">Logs out user. If force == false, begins to perform soft log out, returns authStateLoggingOut after completion. If force == true then succeeds almost immediately without cleaning anything at the server, but returns error with code 401 and description "Unauthorized"</a>  

* <a href="resetNetworkStatistics.html" name="resetNetworkStatistics">Resets all network data usage statistics to zero. Can be called before authorization</a>  

* <a href="searchCallMessages.html" name="searchCallMessages">Searches for call messages. Returns result in reverse chronological order, i. e. in order of decreasing message_id</a>  

* <a href="searchChatMembers.html" name="searchChatMembers">Searches for the specified query in the first name, last name and username among members of the specified chat. Requires administrator rights in broadcast channels</a>  

* <a href="searchChatMessages.html" name="searchChatMessages">Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasing message_id. Doesn't work in secret chats with non-empty query (searchSecretMessages should be used instead) or without enabled message database</a>  

* <a href="searchChats.html" name="searchChats">Searches for specified query in the title and username of known chats, offline request. Returns chats in the order of them in the chat list</a>  

* <a href="searchContacts.html" name="searchContacts">Searches for specified query in the first name, last name and username of the known user contacts</a>  

* <a href="searchHashtags.html" name="searchHashtags">Searches for recently used hashtags by their prefix</a>  

* <a href="searchMessages.html" name="searchMessages">Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)</a>  

* <a href="searchPublicChat.html" name="searchPublicChat">Searches public chat by its username. Currently only private and channel chats can be public. Returns chat if found, otherwise some error is returned</a>  

* <a href="searchPublicChats.html" name="searchPublicChats">Searches public chats by prefix of their username. Currently only private and channel (including supergroup) chats can be public. Returns meaningful number of results. Returns nothing if length of the searched username prefix is less than 5. Excludes private chats with contacts from the results</a>  

* <a href="searchSecretMessages.html" name="searchSecretMessages">Searches for messages in secret chats. Returns result in reverse chronological order</a>  

* <a href="searchStickerSet.html" name="searchStickerSet">Searches sticker set by its short name</a>  

* <a href="sendBotStartMessage.html" name="sendBotStartMessage">Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message</a>  

* <a href="sendChatAction.html" name="sendChatAction">Sends notification about user activity in a chat</a>  

* <a href="sendChatScreenshotTakenNotification.html" name="sendChatScreenshotTakenNotification">Sends notification about screenshot taken in a chat. Works only in private and secret chats</a>  

* <a href="sendChatSetTtlMessage.html" name="sendChatSetTtlMessage">Changes current ttl setting in a secret chat and sends corresponding message</a>  

* <a href="sendCustomRequest.html" name="sendCustomRequest">Bots only. Sends custom request</a>  

* <a href="sendInlineQueryResultMessage.html" name="sendInlineQueryResultMessage">Sends result of the inline query as a message. Returns sent message. Always clears chat draft message</a>  

* <a href="sendMessage.html" name="sendMessage">Sends a message. Returns sent message</a>  

* <a href="sendPaymentForm.html" name="sendPaymentForm">Sends filled payment form to the bot for the final verification</a>  

* <a href="setAlarm.html" name="setAlarm">Returns Ok after specified amount of the time passed. Can be called before authorization</a>  

* <a href="setAuthPhoneNumber.html" name="setAuthPhoneNumber">Sets user's phone number and sends authentication code to the user. Works only when getAuthState returns authStateWaitPhoneNumber. If phone number is not recognized or another error has happened, returns an error. Otherwise returns authStateWaitCode</a>  

* <a href="setBotUpdatesStatus.html" name="setBotUpdatesStatus">Bots only. Informs server about number of pending bot updates if they aren't processed for a long time</a>  

* <a href="setChannelStickerSet.html" name="setChannelStickerSet">Changes sticker set of the channel. Needs appropriate rights in the channel</a>  

* <a href="setChatClientData.html" name="setChatClientData">Changes client data associated with a chat</a>  

* <a href="setFileGenerationProgress.html" name="setFileGenerationProgress">Next part of a file was generated</a>  

* <a href="setGameScore.html" name="setGameScore">Bots only. Updates game score of the specified user in the game</a>  

* <a href="setInlineGameScore.html" name="setInlineGameScore">Bots only. Updates game score of the specified user in the game</a>  

* <a href="setNetworkType.html" name="setNetworkType">Sets current network type. Can be called before authorization. Call to this method forces reopening of all network connections mitigating delay in switching between different networks, so it should be called whenever network is changed even network type remains the same. -Network type is used to check if library can use network at all and for collecting detailed network data usage statistics</a>  

* <a href="setNotificationSettings.html" name="setNotificationSettings">Changes notification settings for a given scope</a>  

* <a href="setOption.html" name="setOption">Sets value of an option. See list of available options on https: core.telegram.org/tdlib/options. Only writable options can be set. Can be called before authorization</a>  

* <a href="setPassword.html" name="setPassword">Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed</a>  

* <a href="setPinnedChats.html" name="setPinnedChats">Changes list or order of pinned chats</a>  

* <a href="setPrivacy.html" name="setPrivacy">Changes privacy settings</a>  

* <a href="setProfilePhoto.html" name="setProfilePhoto">Uploads new profile photo for logged in user. If something changes, updateUser will be sent</a>  

* <a href="setProxy.html" name="setProxy">Sets proxy server for network requests. Can be called before authorization</a>  

* <a href="setRecoveryEmail.html" name="setRecoveryEmail">Changes user recovery email. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and email will not be changed until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed. -If new_recovery_email coincides with the current set up email succeeds immediately and aborts all other requests waiting for email confirmation</a>  

* <a href="setStickerPositionInSet.html" name="setStickerPositionInSet">Bots only. Changes position of a sticker in the set it belongs to. Sticker set should be created by the bot</a>  

* <a href="terminateAllOtherSessions.html" name="terminateAllOtherSessions">Terminates all other sessions of logged in user</a>  

* <a href="terminateSession.html" name="terminateSession">Terminates another session of logged in user</a>  

* <a href="testCallBytes.html" name="testCallBytes">Test request. Returns back received bytes</a>  

* <a href="testCallEmpty.html" name="testCallEmpty">Test request. Does nothing</a>  

* <a href="testCallString.html" name="testCallString">Test request. Returns back received string</a>  

* <a href="testCallVectorInt.html" name="testCallVectorInt">Test request. Returns back received vector of numbers</a>  

* <a href="testCallVectorIntObject.html" name="testCallVectorIntObject">Test request. Returns back received vector of objects containing a number</a>  

* <a href="testCallVectorString.html" name="testCallVectorString">Test request. Returns back received vector of strings</a>  

* <a href="testCallVectorStringObject.html" name="testCallVectorStringObject">Test request. Returns back received vector of objects containing a string</a>  

* <a href="testGetDifference.html" name="testGetDifference">Test request. Forces updates.getDifference call to telegram servers</a>  

* <a href="testNetwork.html" name="testNetwork">Test request. Sends simple network request to telegram servers</a>  

* <a href="testSquareInt.html" name="testSquareInt">Test request. Returns squared received number</a>  

* <a href="testUseError.html" name="testUseError">Test request. Does nothing, ensures that the Error object is used</a>  

* <a href="testUseUpdate.html" name="testUseUpdate">Test request. Does nothing, ensures that the Update object is used</a>  

* <a href="toggleChannelInvites.html" name="toggleChannelInvites">Gives or revokes right to invite new members to all current members of the channel. Needs appropriate rights in the channel. Available only for supergroups</a>  

* <a href="toggleChannelSignMessages.html" name="toggleChannelSignMessages">Enables or disables sender signature on sent messages in the channel. Needs appropriate rights in the channel. Not available for supergroups</a>  

* <a href="toggleChatIsPinned.html" name="toggleChatIsPinned">Changes chat pinned state. You can pin up to getOption("pinned_chat_count_max") non-secret chats and the same number of secret chats</a>  

* <a href="toggleGroupAdministrators.html" name="toggleGroupAdministrators">Gives or revokes all members of the group administrator rights. Needs creator privileges in the group</a>  

* <a href="unblockUser.html" name="unblockUser">Removes user from black list</a>  

* <a href="unpinChannelMessage.html" name="unpinChannelMessage">Removes pinned message in the supergroup channel. Needs appropriate rights in the channel</a>  

* <a href="uploadFile.html" name="uploadFile">Asynchronously uploads file to the cloud without sending it in a message. Updates updateFile will notify about upload progress and successful upload. The file will not have persistent identifier until it will be sent in a message</a>  

* <a href="uploadStickerFile.html" name="uploadStickerFile">Bots only. Uploads a png image with a sticker. Returns uploaded file</a>  

* <a href="validateOrderInfo.html" name="validateOrderInfo">Validates order information provided by the user and returns available shipping options for flexible invoice</a>  

* <a href="viewMessages.html" name="viewMessages">Messages are viewed by the user. Many useful activities depends on message being viewed. For example, marking messages as read, incrementing of view counter, updating of view counter, removing of deleted messages in channels</a>  

* <a href="viewTrendingStickerSets.html" name="viewTrendingStickerSets">Informs that some trending sticker sets are viewed by the user</a>  

