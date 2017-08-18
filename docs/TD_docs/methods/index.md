---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)



***
<br><br>$MadelineProto->[addChatMember](addChatMember.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [int](../types/int.md), 'forward_limit' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="addChatMember"></a>  

***
<br><br>$MadelineProto->[addChatMembers](addChatMembers.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_ids' => \[[int](../types/int.md)\], \]) === [$Ok](../types/Ok.md)<a name="addChatMembers"></a>  

***
<br><br>$MadelineProto->[addRecentSticker](addRecentSticker.md)(\['is_attached' => [Bool](../types/Bool.md), 'sticker' => [InputFile](../types/InputFile.md), \]) === [$Stickers](../types/Stickers.md)<a name="addRecentSticker"></a>  

***
<br><br>$MadelineProto->[addRecentlyFoundChat](addRecentlyFoundChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Ok](../types/Ok.md)<a name="addRecentlyFoundChat"></a>  

***
<br><br>$MadelineProto->[addSavedAnimation](addSavedAnimation.md)(\['animation' => [InputFile](../types/InputFile.md), \]) === [$Ok](../types/Ok.md)<a name="addSavedAnimation"></a>  

***
<br><br>$MadelineProto->[answerCallbackQuery](answerCallbackQuery.md)(\['callback_query_id' => [long](../types/long.md), 'text' => [string](../types/string.md), 'show_alert' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'cache_time' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="answerCallbackQuery"></a>  

***
<br><br>$MadelineProto->[answerInlineQuery](answerInlineQuery.md)(\['inline_query_id' => [long](../types/long.md), 'is_personal' => [Bool](../types/Bool.md), 'results' => \[[InputInlineQueryResult](../types/InputInlineQueryResult.md)\], 'cache_time' => [int](../types/int.md), 'next_offset' => [string](../types/string.md), 'switch_pm_text' => [string](../types/string.md), 'switch_pm_parameter' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="answerInlineQuery"></a>  

***
<br><br>$MadelineProto->[blockUser](blockUser.md)(\['user_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="blockUser"></a>  

***
<br><br>$MadelineProto->[cancelDownloadFile](cancelDownloadFile.md)(\['file_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="cancelDownloadFile"></a>  

***
<br><br>$MadelineProto->[changeAbout](changeAbout.md)(\['about' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeAbout"></a>  

***
<br><br>$MadelineProto->[changeAccountTtl](changeAccountTtl.md)(\['ttl' => [accountTtl](../constructors/accountTtl.md), \]) === [$Ok](../types/Ok.md)<a name="changeAccountTtl"></a>  

***
<br><br>$MadelineProto->[changeChannelAbout](changeChannelAbout.md)(\['channel_id' => [int](../types/int.md), 'about' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeChannelAbout"></a>  

***
<br><br>$MadelineProto->[changeChannelUsername](changeChannelUsername.md)(\['channel_id' => [int](../types/int.md), 'username' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeChannelUsername"></a>  

***
<br><br>$MadelineProto->[changeChatDraftMessage](changeChatDraftMessage.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'draft_message' => [draftMessage](../constructors/draftMessage.md), \]) === [$Ok](../types/Ok.md)<a name="changeChatDraftMessage"></a>  

***
<br><br>$MadelineProto->[changeChatMemberStatus](changeChatMemberStatus.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), \]) === [$Ok](../types/Ok.md)<a name="changeChatMemberStatus"></a>  

***
<br><br>$MadelineProto->[changeChatPhoto](changeChatPhoto.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'photo' => [InputFile](../types/InputFile.md), \]) === [$Ok](../types/Ok.md)<a name="changeChatPhoto"></a>  

***
<br><br>$MadelineProto->[changeChatReportSpamState](changeChatReportSpamState.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'is_spam_chat' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="changeChatReportSpamState"></a>  

***
<br><br>$MadelineProto->[changeChatTitle](changeChatTitle.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'title' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeChatTitle"></a>  

***
<br><br>$MadelineProto->[changeName](changeName.md)(\['first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeName"></a>  

***
<br><br>$MadelineProto->[changePhoneNumber](changePhoneNumber.md)(\['phone_number' => [string](../types/string.md), 'allow_flash_call' => [Bool](../types/Bool.md), 'is_current_phone_number' => [Bool](../types/Bool.md), \]) === [$AuthState](../types/AuthState.md)<a name="changePhoneNumber"></a>  

***
<br><br>$MadelineProto->[changeUsername](changeUsername.md)(\['username' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="changeUsername"></a>  

***
<br><br>$MadelineProto->[checkAuthBotToken](checkAuthBotToken.md)(\['token' => [string](../types/string.md), \]) === [$AuthState](../types/AuthState.md)<a name="checkAuthBotToken"></a>  

***
<br><br>$MadelineProto->[checkAuthCode](checkAuthCode.md)(\['code' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) === [$AuthState](../types/AuthState.md)<a name="checkAuthCode"></a>  

***
<br><br>$MadelineProto->[checkAuthPassword](checkAuthPassword.md)(\['password' => [string](../types/string.md), \]) === [$AuthState](../types/AuthState.md)<a name="checkAuthPassword"></a>  

***
<br><br>$MadelineProto->[checkChangePhoneNumberCode](checkChangePhoneNumberCode.md)(\['code' => [string](../types/string.md), \]) === [$AuthState](../types/AuthState.md)<a name="checkChangePhoneNumberCode"></a>  

***
<br><br>$MadelineProto->[checkChatInviteLink](checkChatInviteLink.md)(\['invite_link' => [string](../types/string.md), \]) === [$ChatInviteLinkInfo](../types/ChatInviteLinkInfo.md)<a name="checkChatInviteLink"></a>  

***
<br><br>$MadelineProto->[clearRecentStickers](clearRecentStickers.md)(\['is_attached' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="clearRecentStickers"></a>  

***
<br><br>$MadelineProto->[closeChat](closeChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Ok](../types/Ok.md)<a name="closeChat"></a>  

***
<br><br>$MadelineProto->[closeSecretChat](closeSecretChat.md)(\['secret_chat_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="closeSecretChat"></a>  

***
<br><br>$MadelineProto->[createChannelChat](createChannelChat.md)(\['channel_id' => [int](../types/int.md), \]) === [$Chat](../types/Chat.md)<a name="createChannelChat"></a>  

***
<br><br>$MadelineProto->[createGroupChat](createGroupChat.md)(\['group_id' => [int](../types/int.md), \]) === [$Chat](../types/Chat.md)<a name="createGroupChat"></a>  

***
<br><br>$MadelineProto->[createNewChannelChat](createNewChannelChat.md)(\['title' => [string](../types/string.md), 'is_supergroup' => [Bool](../types/Bool.md), 'about' => [string](../types/string.md), \]) === [$Chat](../types/Chat.md)<a name="createNewChannelChat"></a>  

***
<br><br>$MadelineProto->[createNewGroupChat](createNewGroupChat.md)(\['user_ids' => \[[int](../types/int.md)\], 'title' => [string](../types/string.md), \]) === [$Chat](../types/Chat.md)<a name="createNewGroupChat"></a>  

***
<br><br>$MadelineProto->[createNewSecretChat](createNewSecretChat.md)(\['user_id' => [int](../types/int.md), \]) === [$Chat](../types/Chat.md)<a name="createNewSecretChat"></a>  

***
<br><br>$MadelineProto->[createPrivateChat](createPrivateChat.md)(\['user_id' => [int](../types/int.md), \]) === [$Chat](../types/Chat.md)<a name="createPrivateChat"></a>  

***
<br><br>$MadelineProto->[createSecretChat](createSecretChat.md)(\['secret_chat_id' => [int](../types/int.md), \]) === [$Chat](../types/Chat.md)<a name="createSecretChat"></a>  

***
<br><br>$MadelineProto->[deleteAccount](deleteAccount.md)(\['reason' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="deleteAccount"></a>  

***
<br><br>$MadelineProto->[deleteChannel](deleteChannel.md)(\['channel_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="deleteChannel"></a>  

***
<br><br>$MadelineProto->[deleteChatHistory](deleteChatHistory.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'remove_from_chat_list' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="deleteChatHistory"></a>  

***
<br><br>$MadelineProto->[deleteChatReplyMarkup](deleteChatReplyMarkup.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), \]) === [$Ok](../types/Ok.md)<a name="deleteChatReplyMarkup"></a>  

***
<br><br>$MadelineProto->[deleteContacts](deleteContacts.md)(\['user_ids' => \[[int](../types/int.md)\], \]) === [$Ok](../types/Ok.md)<a name="deleteContacts"></a>  

***
<br><br>$MadelineProto->[deleteMessages](deleteMessages.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_ids' => \[[long](../types/long.md)\], \]) === [$Ok](../types/Ok.md)<a name="deleteMessages"></a>  

***
<br><br>$MadelineProto->[deleteMessagesFromUser](deleteMessagesFromUser.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="deleteMessagesFromUser"></a>  

***
<br><br>$MadelineProto->[deleteProfilePhoto](deleteProfilePhoto.md)(\['profile_photo_id' => [long](../types/long.md), \]) === [$Ok](../types/Ok.md)<a name="deleteProfilePhoto"></a>  

***
<br><br>$MadelineProto->[deleteRecentSticker](deleteRecentSticker.md)(\['is_attached' => [Bool](../types/Bool.md), 'sticker' => [InputFile](../types/InputFile.md), \]) === [$Ok](../types/Ok.md)<a name="deleteRecentSticker"></a>  

***
<br><br>$MadelineProto->[deleteRecentlyFoundChat](deleteRecentlyFoundChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Ok](../types/Ok.md)<a name="deleteRecentlyFoundChat"></a>  

***
<br><br>$MadelineProto->[deleteRecentlyFoundChats](deleteRecentlyFoundChats.md)(\[\]) === [$Ok](../types/Ok.md)<a name="deleteRecentlyFoundChats"></a>  

***
<br><br>$MadelineProto->[deleteSavedAnimation](deleteSavedAnimation.md)(\['animation' => [InputFile](../types/InputFile.md), \]) === [$Ok](../types/Ok.md)<a name="deleteSavedAnimation"></a>  

***
<br><br>$MadelineProto->[downloadFile](downloadFile.md)(\['file_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="downloadFile"></a>  

***
<br><br>$MadelineProto->[editInlineMessageCaption](editInlineMessageCaption.md)(\['inline_message_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'caption' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="editInlineMessageCaption"></a>  

***
<br><br>$MadelineProto->[editInlineMessageReplyMarkup](editInlineMessageReplyMarkup.md)(\['inline_message_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) === [$Ok](../types/Ok.md)<a name="editInlineMessageReplyMarkup"></a>  

***
<br><br>$MadelineProto->[editInlineMessageText](editInlineMessageText.md)(\['inline_message_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \]) === [$Ok](../types/Ok.md)<a name="editInlineMessageText"></a>  

***
<br><br>$MadelineProto->[editMessageCaption](editMessageCaption.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'caption' => [string](../types/string.md), \]) === [$Message](../types/Message.md)<a name="editMessageCaption"></a>  

***
<br><br>$MadelineProto->[editMessageReplyMarkup](editMessageReplyMarkup.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) === [$Message](../types/Message.md)<a name="editMessageReplyMarkup"></a>  

***
<br><br>$MadelineProto->[editMessageText](editMessageText.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \]) === [$Message](../types/Message.md)<a name="editMessageText"></a>  

***
<br><br>$MadelineProto->[exportChatInviteLink](exportChatInviteLink.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$ChatInviteLink](../types/ChatInviteLink.md)<a name="exportChatInviteLink"></a>  

***
<br><br>$MadelineProto->[finishFileGeneration](finishFileGeneration.md)(\['generation_id' => [long](../types/long.md), \]) === [$Ok](../types/Ok.md)<a name="finishFileGeneration"></a>  

***
<br><br>$MadelineProto->[forwardMessages](forwardMessages.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'from_chat_id' => [long](../types/long.md), 'message_ids' => \[[long](../types/long.md)\], 'disable_notification' => [Bool](../types/Bool.md), 'from_background' => [Bool](../types/Bool.md), \]) === [$Messages](../types/Messages.md)<a name="forwardMessages"></a>  

***
<br><br>$MadelineProto->[getAccountTtl](getAccountTtl.md)(\[\]) === [$AccountTtl](../types/AccountTtl.md)<a name="getAccountTtl"></a>  

***
<br><br>$MadelineProto->[getActiveSessions](getActiveSessions.md)(\[\]) === [$Sessions](../types/Sessions.md)<a name="getActiveSessions"></a>  

***
<br><br>$MadelineProto->[getArchivedStickerSets](getArchivedStickerSets.md)(\['is_masks' => [Bool](../types/Bool.md), 'offset_sticker_set_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) === [$StickerSets](../types/StickerSets.md)<a name="getArchivedStickerSets"></a>  

***
<br><br>$MadelineProto->[getAttachedStickerSets](getAttachedStickerSets.md)(\['file_id' => [int](../types/int.md), \]) === [$StickerSets](../types/StickerSets.md)<a name="getAttachedStickerSets"></a>  

***
<br><br>$MadelineProto->[getAuthState](getAuthState.md)(\[\]) === [$AuthState](../types/AuthState.md)<a name="getAuthState"></a>  

***
<br><br>$MadelineProto->[getBlockedUsers](getBlockedUsers.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$Users](../types/Users.md)<a name="getBlockedUsers"></a>  

***
<br><br>$MadelineProto->[getCallbackQueryAnswer](getCallbackQueryAnswer.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'payload' => [CallbackQueryPayload](../types/CallbackQueryPayload.md), \]) === [$CallbackQueryAnswer](../types/CallbackQueryAnswer.md)<a name="getCallbackQueryAnswer"></a>  

***
<br><br>$MadelineProto->[getChannel](getChannel.md)(\['channel_id' => [int](../types/int.md), \]) === [$Channel](../types/Channel.md)<a name="getChannel"></a>  

***
<br><br>$MadelineProto->[getChannelFull](getChannelFull.md)(\['channel_id' => [int](../types/int.md), \]) === [$ChannelFull](../types/ChannelFull.md)<a name="getChannelFull"></a>  

***
<br><br>$MadelineProto->[getChannelMembers](getChannelMembers.md)(\['channel_id' => [int](../types/int.md), 'filter' => [ChannelMembersFilter](../types/ChannelMembersFilter.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$ChatMembers](../types/ChatMembers.md)<a name="getChannelMembers"></a>  

***
<br><br>$MadelineProto->[getChat](getChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Chat](../types/Chat.md)<a name="getChat"></a>  

***
<br><br>$MadelineProto->[getChatHistory](getChatHistory.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'from_message_id' => [long](../types/long.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$Messages](../types/Messages.md)<a name="getChatHistory"></a>  

***
<br><br>$MadelineProto->[getChatMember](getChatMember.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [int](../types/int.md), \]) === [$ChatMember](../types/ChatMember.md)<a name="getChatMember"></a>  

***
<br><br>$MadelineProto->[getChatReportSpamState](getChatReportSpamState.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$ChatReportSpamState](../types/ChatReportSpamState.md)<a name="getChatReportSpamState"></a>  

***
<br><br>$MadelineProto->[getChats](getChats.md)(\['offset_order' => [long](../types/long.md), 'offset_chat_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) === [$Chats](../types/Chats.md)<a name="getChats"></a>  

***
<br><br>$MadelineProto->[getCommonChats](getCommonChats.md)(\['user_id' => [int](../types/int.md), 'offset_chat_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) === [$Chats](../types/Chats.md)<a name="getCommonChats"></a>  

***
<br><br>$MadelineProto->[getCreatedPublicChannels](getCreatedPublicChannels.md)(\[\]) === [$Channels](../types/Channels.md)<a name="getCreatedPublicChannels"></a>  

***
<br><br>$MadelineProto->[getDeviceTokens](getDeviceTokens.md)(\[\]) === [$DeviceTokenSet](../types/DeviceTokenSet.md)<a name="getDeviceTokens"></a>  

***
<br><br>$MadelineProto->[getFile](getFile.md)(\['file_id' => [int](../types/int.md), \]) === [$File](../types/File.md)<a name="getFile"></a>  

***
<br><br>$MadelineProto->[getFilePersistent](getFilePersistent.md)(\['persistent_file_id' => [string](../types/string.md), \]) === [$File](../types/File.md)<a name="getFilePersistent"></a>  

***
<br><br>$MadelineProto->[getGameHighScores](getGameHighScores.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), \]) === [$GameHighScores](../types/GameHighScores.md)<a name="getGameHighScores"></a>  

***
<br><br>$MadelineProto->[getGroup](getGroup.md)(\['group_id' => [int](../types/int.md), \]) === [$Group](../types/Group.md)<a name="getGroup"></a>  

***
<br><br>$MadelineProto->[getGroupFull](getGroupFull.md)(\['group_id' => [int](../types/int.md), \]) === [$GroupFull](../types/GroupFull.md)<a name="getGroupFull"></a>  

***
<br><br>$MadelineProto->[getInlineGameHighScores](getInlineGameHighScores.md)(\['inline_message_id' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \]) === [$GameHighScores](../types/GameHighScores.md)<a name="getInlineGameHighScores"></a>  

***
<br><br>$MadelineProto->[getInlineQueryResults](getInlineQueryResults.md)(\['bot_user_id' => [int](../types/int.md), 'chat_id' => [InputPeer](../types/InputPeer.md), 'user_location' => [location](../constructors/location.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \]) === [$InlineQueryResults](../types/InlineQueryResults.md)<a name="getInlineQueryResults"></a>  

***
<br><br>$MadelineProto->[getMe](getMe.md)(\[\]) === [$User](../types/User.md)<a name="getMe"></a>  

***
<br><br>$MadelineProto->[getMessage](getMessage.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), \]) === [$Message](../types/Message.md)<a name="getMessage"></a>  

***
<br><br>$MadelineProto->[getMessages](getMessages.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_ids' => \[[long](../types/long.md)\], \]) === [$Messages](../types/Messages.md)<a name="getMessages"></a>  

***
<br><br>$MadelineProto->[getNotificationSettings](getNotificationSettings.md)(\['scope' => [NotificationSettingsScope](../types/NotificationSettingsScope.md), \]) === [$NotificationSettings](../types/NotificationSettings.md)<a name="getNotificationSettings"></a>  

***
<br><br>$MadelineProto->[getOption](getOption.md)(\['name' => [string](../types/string.md), \]) === [$OptionValue](../types/OptionValue.md)<a name="getOption"></a>  

***
<br><br>$MadelineProto->[getPasswordState](getPasswordState.md)(\[\]) === [$PasswordState](../types/PasswordState.md)<a name="getPasswordState"></a>  

***
<br><br>$MadelineProto->[getPrivacy](getPrivacy.md)(\['key' => [PrivacyKey](../types/PrivacyKey.md), \]) === [$PrivacyRules](../types/PrivacyRules.md)<a name="getPrivacy"></a>  

***
<br><br>$MadelineProto->[getRecentInlineBots](getRecentInlineBots.md)(\[\]) === [$Users](../types/Users.md)<a name="getRecentInlineBots"></a>  

***
<br><br>$MadelineProto->[getRecentStickers](getRecentStickers.md)(\['is_attached' => [Bool](../types/Bool.md), \]) === [$Stickers](../types/Stickers.md)<a name="getRecentStickers"></a>  

***
<br><br>$MadelineProto->[getRecoveryEmail](getRecoveryEmail.md)(\['password' => [string](../types/string.md), \]) === [$RecoveryEmail](../types/RecoveryEmail.md)<a name="getRecoveryEmail"></a>  

***
<br><br>$MadelineProto->[getSavedAnimations](getSavedAnimations.md)(\[\]) === [$Animations](../types/Animations.md)<a name="getSavedAnimations"></a>  

***
<br><br>$MadelineProto->[getSecretChat](getSecretChat.md)(\['secret_chat_id' => [int](../types/int.md), \]) === [$SecretChat](../types/SecretChat.md)<a name="getSecretChat"></a>  

***
<br><br>$MadelineProto->[getStickerEmojis](getStickerEmojis.md)(\['sticker' => [InputFile](../types/InputFile.md), \]) === [$StickerEmojis](../types/StickerEmojis.md)<a name="getStickerEmojis"></a>  

***
<br><br>$MadelineProto->[getStickerSet](getStickerSet.md)(\['set_id' => [long](../types/long.md), \]) === [$StickerSet](../types/StickerSet.md)<a name="getStickerSet"></a>  

***
<br><br>$MadelineProto->[getStickerSets](getStickerSets.md)(\['is_masks' => [Bool](../types/Bool.md), \]) === [$StickerSets](../types/StickerSets.md)<a name="getStickerSets"></a>  

***
<br><br>$MadelineProto->[getStickers](getStickers.md)(\['emoji' => [string](../types/string.md), \]) === [$Stickers](../types/Stickers.md)<a name="getStickers"></a>  

***
<br><br>$MadelineProto->[getSupportUser](getSupportUser.md)(\[\]) === [$User](../types/User.md)<a name="getSupportUser"></a>  

***
<br><br>$MadelineProto->[getTrendingStickerSets](getTrendingStickerSets.md)(\[\]) === [$StickerSets](../types/StickerSets.md)<a name="getTrendingStickerSets"></a>  

***
<br><br>$MadelineProto->[getUser](getUser.md)(\['user_id' => [int](../types/int.md), \]) === [$User](../types/User.md)<a name="getUser"></a>  

***
<br><br>$MadelineProto->[getUserFull](getUserFull.md)(\['user_id' => [int](../types/int.md), \]) === [$UserFull](../types/UserFull.md)<a name="getUserFull"></a>  

***
<br><br>$MadelineProto->[getUserProfilePhotos](getUserProfilePhotos.md)(\['user_id' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$UserProfilePhotos](../types/UserProfilePhotos.md)<a name="getUserProfilePhotos"></a>  

***
<br><br>$MadelineProto->[getWallpapers](getWallpapers.md)(\[\]) === [$Wallpapers](../types/Wallpapers.md)<a name="getWallpapers"></a>  

***
<br><br>$MadelineProto->[getWebPagePreview](getWebPagePreview.md)(\['message_text' => [string](../types/string.md), \]) === [$WebPage](../types/WebPage.md)<a name="getWebPagePreview"></a>  

***
<br><br>$MadelineProto->[importChatInviteLink](importChatInviteLink.md)(\['invite_link' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="importChatInviteLink"></a>  

***
<br><br>$MadelineProto->[importContacts](importContacts.md)(\['contacts' => \[[contact](../constructors/contact.md)\], \]) === [$Users](../types/Users.md)<a name="importContacts"></a>  

***
<br><br>$MadelineProto->[migrateGroupChatToChannelChat](migrateGroupChatToChannelChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Chat](../types/Chat.md)<a name="migrateGroupChatToChannelChat"></a>  

***
<br><br>$MadelineProto->[openChat](openChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Ok](../types/Ok.md)<a name="openChat"></a>  

***
<br><br>$MadelineProto->[openMessageContent](openMessageContent.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), \]) === [$Ok](../types/Ok.md)<a name="openMessageContent"></a>  

***
<br><br>$MadelineProto->[pinChannelMessage](pinChannelMessage.md)(\['channel_id' => [int](../types/int.md), 'message_id' => [long](../types/long.md), 'disable_notification' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="pinChannelMessage"></a>  

***
<br><br>$MadelineProto->[recoverAuthPassword](recoverAuthPassword.md)(\['recovery_code' => [string](../types/string.md), \]) === [$AuthState](../types/AuthState.md)<a name="recoverAuthPassword"></a>  

***
<br><br>$MadelineProto->[recoverPassword](recoverPassword.md)(\['recovery_code' => [string](../types/string.md), \]) === [$PasswordState](../types/PasswordState.md)<a name="recoverPassword"></a>  

***
<br><br>$MadelineProto->[registerDevice](registerDevice.md)(\['device_token' => [DeviceToken](../types/DeviceToken.md), \]) === [$Ok](../types/Ok.md)<a name="registerDevice"></a>  

***
<br><br>$MadelineProto->[reorderStickerSets](reorderStickerSets.md)(\['is_masks' => [Bool](../types/Bool.md), 'sticker_set_ids' => \[[long](../types/long.md)\], \]) === [$Ok](../types/Ok.md)<a name="reorderStickerSets"></a>  

***
<br><br>$MadelineProto->[reportChannelSpam](reportChannelSpam.md)(\['channel_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'message_ids' => \[[long](../types/long.md)\], \]) === [$Ok](../types/Ok.md)<a name="reportChannelSpam"></a>  

***
<br><br>$MadelineProto->[requestAuthPasswordRecovery](requestAuthPasswordRecovery.md)(\[\]) === [$AuthState](../types/AuthState.md)<a name="requestAuthPasswordRecovery"></a>  

***
<br><br>$MadelineProto->[requestPasswordRecovery](requestPasswordRecovery.md)(\[\]) === [$PasswordRecoveryInfo](../types/PasswordRecoveryInfo.md)<a name="requestPasswordRecovery"></a>  

***
<br><br>$MadelineProto->[resendAuthCode](resendAuthCode.md)(\[\]) === [$AuthState](../types/AuthState.md)<a name="resendAuthCode"></a>  

***
<br><br>$MadelineProto->[resendChangePhoneNumberCode](resendChangePhoneNumberCode.md)(\[\]) === [$AuthState](../types/AuthState.md)<a name="resendChangePhoneNumberCode"></a>  

***
<br><br>$MadelineProto->[resetAllNotificationSettings](resetAllNotificationSettings.md)(\[\]) === [$Ok](../types/Ok.md)<a name="resetAllNotificationSettings"></a>  

***
<br><br>$MadelineProto->[resetAuth](resetAuth.md)(\['force' => [Bool](../types/Bool.md), \]) === [$AuthState](../types/AuthState.md)<a name="resetAuth"></a>  

***
<br><br>$MadelineProto->[searchChatMessages](searchChatMessages.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'query' => [string](../types/string.md), 'from_message_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), 'filter' => [SearchMessagesFilter](../types/SearchMessagesFilter.md), \]) === [$Messages](../types/Messages.md)<a name="searchChatMessages"></a>  

***
<br><br>$MadelineProto->[searchChats](searchChats.md)(\['query' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) === [$Chats](../types/Chats.md)<a name="searchChats"></a>  

***
<br><br>$MadelineProto->[searchContacts](searchContacts.md)(\['query' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) === [$Users](../types/Users.md)<a name="searchContacts"></a>  

***
<br><br>$MadelineProto->[searchMessages](searchMessages.md)(\['query' => [string](../types/string.md), 'offset_date' => [int](../types/int.md), 'offset_chat_id' => [long](../types/long.md), 'offset_message_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) === [$Messages](../types/Messages.md)<a name="searchMessages"></a>  

***
<br><br>$MadelineProto->[searchPublicChat](searchPublicChat.md)(\['username' => [string](../types/string.md), \]) === [$Chat](../types/Chat.md)<a name="searchPublicChat"></a>  

***
<br><br>$MadelineProto->[searchPublicChats](searchPublicChats.md)(\['username_prefix' => [string](../types/string.md), \]) === [$Chats](../types/Chats.md)<a name="searchPublicChats"></a>  

***
<br><br>$MadelineProto->[searchStickerSet](searchStickerSet.md)(\['name' => [string](../types/string.md), \]) === [$StickerSet](../types/StickerSet.md)<a name="searchStickerSet"></a>  

***
<br><br>$MadelineProto->[sendBotStartMessage](sendBotStartMessage.md)(\['bot_user_id' => [int](../types/int.md), 'chat_id' => [InputPeer](../types/InputPeer.md), 'parameter' => [string](../types/string.md), \]) === [$Message](../types/Message.md)<a name="sendBotStartMessage"></a>  

***
<br><br>$MadelineProto->[sendChatAction](sendChatAction.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) === [$Ok](../types/Ok.md)<a name="sendChatAction"></a>  

***
<br><br>$MadelineProto->[sendChatScreenshotTakenNotification](sendChatScreenshotTakenNotification.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$Ok](../types/Ok.md)<a name="sendChatScreenshotTakenNotification"></a>  

***
<br><br>$MadelineProto->[sendChatSetTtlMessage](sendChatSetTtlMessage.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'ttl' => [int](../types/int.md), \]) === [$Message](../types/Message.md)<a name="sendChatSetTtlMessage"></a>  

***
<br><br>$MadelineProto->[sendInlineQueryResultMessage](sendInlineQueryResultMessage.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'reply_to_message_id' => [long](../types/long.md), 'disable_notification' => [Bool](../types/Bool.md), 'from_background' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'result_id' => [string](../types/string.md), \]) === [$Message](../types/Message.md)<a name="sendInlineQueryResultMessage"></a>  

***
<br><br>$MadelineProto->[sendMessage](sendMessage.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'reply_to_message_id' => [long](../types/long.md), 'disable_notification' => [Bool](../types/Bool.md), 'from_background' => [Bool](../types/Bool.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \]) === [$Message](../types/Message.md)<a name="sendMessage"></a>  

***
<br><br>$MadelineProto->[setAlarm](setAlarm.md)(\['seconds' => [double](../types/double.md), \]) === [$Ok](../types/Ok.md)<a name="setAlarm"></a>  

***
<br><br>$MadelineProto->[setAuthPhoneNumber](setAuthPhoneNumber.md)(\['phone_number' => [string](../types/string.md), 'allow_flash_call' => [Bool](../types/Bool.md), 'is_current_phone_number' => [Bool](../types/Bool.md), \]) === [$AuthState](../types/AuthState.md)<a name="setAuthPhoneNumber"></a>  

***
<br><br>$MadelineProto->[setBotUpdatesStatus](setBotUpdatesStatus.md)(\['pending_update_count' => [int](../types/int.md), 'error_message' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="setBotUpdatesStatus"></a>  

***
<br><br>$MadelineProto->[setFileGenerationProgress](setFileGenerationProgress.md)(\['generation_id' => [long](../types/long.md), 'ready' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="setFileGenerationProgress"></a>  

***
<br><br>$MadelineProto->[setGameScore](setGameScore.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_id' => [long](../types/long.md), 'edit_message' => [Bool](../types/Bool.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), 'force' => [Bool](../types/Bool.md), \]) === [$Message](../types/Message.md)<a name="setGameScore"></a>  

***
<br><br>$MadelineProto->[setInlineGameScore](setInlineGameScore.md)(\['inline_message_id' => [string](../types/string.md), 'edit_message' => [Bool](../types/Bool.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), 'force' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="setInlineGameScore"></a>  

***
<br><br>$MadelineProto->[setNotificationSettings](setNotificationSettings.md)(\['scope' => [NotificationSettingsScope](../types/NotificationSettingsScope.md), 'notification_settings' => [notificationSettings](../constructors/notificationSettings.md), \]) === [$Ok](../types/Ok.md)<a name="setNotificationSettings"></a>  

***
<br><br>$MadelineProto->[setOption](setOption.md)(\['name' => [string](../types/string.md), 'value' => [OptionValue](../types/OptionValue.md), \]) === [$Ok](../types/Ok.md)<a name="setOption"></a>  

***
<br><br>$MadelineProto->[setPassword](setPassword.md)(\['old_password' => [string](../types/string.md), 'new_password' => [string](../types/string.md), 'new_hint' => [string](../types/string.md), 'set_recovery_email' => [Bool](../types/Bool.md), 'new_recovery_email' => [string](../types/string.md), \]) === [$PasswordState](../types/PasswordState.md)<a name="setPassword"></a>  

***
<br><br>$MadelineProto->[setPrivacy](setPrivacy.md)(\['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => [privacyRules](../constructors/privacyRules.md), \]) === [$Ok](../types/Ok.md)<a name="setPrivacy"></a>  

***
<br><br>$MadelineProto->[setProfilePhoto](setProfilePhoto.md)(\['photo_path' => [string](../types/string.md), \]) === [$Ok](../types/Ok.md)<a name="setProfilePhoto"></a>  

***
<br><br>$MadelineProto->[setRecoveryEmail](setRecoveryEmail.md)(\['password' => [string](../types/string.md), 'new_recovery_email' => [string](../types/string.md), \]) === [$PasswordState](../types/PasswordState.md)<a name="setRecoveryEmail"></a>  

***
<br><br>$MadelineProto->[terminateAllOtherSessions](terminateAllOtherSessions.md)(\[\]) === [$Ok](../types/Ok.md)<a name="terminateAllOtherSessions"></a>  

***
<br><br>$MadelineProto->[terminateSession](terminateSession.md)(\['session_id' => [long](../types/long.md), \]) === [$Ok](../types/Ok.md)<a name="terminateSession"></a>  

***
<br><br>$MadelineProto->[test->callBytes](test_callBytes.md)(\['x' => [bytes](../types/bytes.md), \]) === [$test\_Bytes](../types/test_Bytes.md)<a name="test_callBytes"></a>  

$MadelineProto->[test->callEmpty](test_callEmpty.md)(\[\]) === [$test\_Empty](../types/test_Empty.md)<a name="test_callEmpty"></a>  

$MadelineProto->[test->callString](test_callString.md)(\['x' => [string](../types/string.md), \]) === [$test\_String](../types/test_String.md)<a name="test_callString"></a>  

$MadelineProto->[test->callVectorInt](test_callVectorInt.md)(\['x' => \[[int](../types/int.md)\], \]) === [$test\_VectorInt](../types/test_VectorInt.md)<a name="test_callVectorInt"></a>  

$MadelineProto->[test->callVectorIntObject](test_callVectorIntObject.md)(\['x' => \[[test\_Int](../types/test_Int.md)\], \]) === [$test\_VectorIntObject](../types/test_VectorIntObject.md)<a name="test_callVectorIntObject"></a>  

$MadelineProto->[test->callVectorString](test_callVectorString.md)(\['x' => \[[string](../types/string.md)\], \]) === [$test\_VectorString](../types/test_VectorString.md)<a name="test_callVectorString"></a>  

$MadelineProto->[test->callVectorStringObject](test_callVectorStringObject.md)(\['x' => \[[test\_String](../types/test_String.md)\], \]) === [$test\_VectorStringObject](../types/test_VectorStringObject.md)<a name="test_callVectorStringObject"></a>  

$MadelineProto->[test->forceGetDifference](test_forceGetDifference.md)(\[\]) === [$Ok](../types/Ok.md)<a name="test_forceGetDifference"></a>  

$MadelineProto->[test->squareInt](test_squareInt.md)(\['x' => [int](../types/int.md), \]) === [$test\_Int](../types/test_Int.md)<a name="test_squareInt"></a>  

$MadelineProto->[test->testNet](test_testNet.md)(\[\]) === [$test\_Empty](../types/test_Empty.md)<a name="test_testNet"></a>  

***
<br><br>$MadelineProto->[toggleChannelInvites](toggleChannelInvites.md)(\['channel_id' => [int](../types/int.md), 'anyone_can_invite' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="toggleChannelInvites"></a>  

***
<br><br>$MadelineProto->[toggleChannelSignMessages](toggleChannelSignMessages.md)(\['channel_id' => [int](../types/int.md), 'sign_messages' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="toggleChannelSignMessages"></a>  

***
<br><br>$MadelineProto->[toggleGroupEditors](toggleGroupEditors.md)(\['group_id' => [int](../types/int.md), 'anyone_can_edit' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="toggleGroupEditors"></a>  

***
<br><br>$MadelineProto->[unblockUser](unblockUser.md)(\['user_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="unblockUser"></a>  

***
<br><br>$MadelineProto->[unpinChannelMessage](unpinChannelMessage.md)(\['channel_id' => [int](../types/int.md), \]) === [$Ok](../types/Ok.md)<a name="unpinChannelMessage"></a>  

***
<br><br>$MadelineProto->[updateStickerSet](updateStickerSet.md)(\['set_id' => [long](../types/long.md), 'is_installed' => [Bool](../types/Bool.md), 'is_archived' => [Bool](../types/Bool.md), \]) === [$Ok](../types/Ok.md)<a name="updateStickerSet"></a>  

***
<br><br>$MadelineProto->[viewMessages](viewMessages.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'message_ids' => \[[long](../types/long.md)\], \]) === [$Ok](../types/Ok.md)<a name="viewMessages"></a>  

***
<br><br>$MadelineProto->[viewTrendingStickerSets](viewTrendingStickerSets.md)(\['sticker_set_ids' => \[[long](../types/long.md)\], \]) === [$Ok](../types/Ok.md)<a name="viewTrendingStickerSets"></a>  

