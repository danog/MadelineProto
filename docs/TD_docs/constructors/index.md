---
title: Constructors
description: List of constructors
---
# Constructors  
[Back to API documentation index](..)
***
<br><br>[$accountTtl](../constructors/accountTtl.md) = \['days' => [int](../types/int.md), \];<a name="accountTtl"></a>  

***
<br><br>[$animation](../constructors/animation.md) = \['width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'animation' => [file](../constructors/file.md), \];<a name="animation"></a>  

***
<br><br>[$animations](../constructors/animations.md) = \['animations' => \[[animation](../constructors/animation.md)\], \];<a name="animations"></a>  

***
<br><br>[$apnsDeviceToken](../constructors/apnsDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="apnsDeviceToken"></a>  

***
<br><br>[$audio](../constructors/audio.md) = \['duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'album_cover_thumb' => [photoSize](../constructors/photoSize.md), 'audio' => [file](../constructors/file.md), \];<a name="audio"></a>  

***
<br><br>[$authCodeTypeCall](../constructors/authCodeTypeCall.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeCall"></a>  

***
<br><br>[$authCodeTypeFlashCall](../constructors/authCodeTypeFlashCall.md) = \['pattern' => [string](../types/string.md), \];<a name="authCodeTypeFlashCall"></a>  

***
<br><br>[$authCodeTypeMessage](../constructors/authCodeTypeMessage.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeMessage"></a>  

***
<br><br>[$authCodeTypeSms](../constructors/authCodeTypeSms.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeSms"></a>  

***
<br><br>[$authStateLoggingOut](../constructors/authStateLoggingOut.md) = \[\];<a name="authStateLoggingOut"></a>  

***
<br><br>[$authStateOk](../constructors/authStateOk.md) = \[\];<a name="authStateOk"></a>  

***
<br><br>[$authStateWaitCode](../constructors/authStateWaitCode.md) = \['is_registered' => [Bool](../types/Bool.md), 'code_type' => [AuthCodeType](../types/AuthCodeType.md), 'next_code_type' => [AuthCodeType](../types/AuthCodeType.md), 'timeout' => [int](../types/int.md), \];<a name="authStateWaitCode"></a>  

***
<br><br>[$authStateWaitPassword](../constructors/authStateWaitPassword.md) = \['password_hint' => [string](../types/string.md), 'has_recovery_email' => [Bool](../types/Bool.md), 'recovery_email_pattern' => [string](../types/string.md), \];<a name="authStateWaitPassword"></a>  

***
<br><br>[$authStateWaitPhoneNumber](../constructors/authStateWaitPhoneNumber.md) = \[\];<a name="authStateWaitPhoneNumber"></a>  

***
<br><br>[$blackberryDeviceToken](../constructors/blackberryDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="blackberryDeviceToken"></a>  

***
<br><br>[$botCommand](../constructors/botCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="botCommand"></a>  

***
<br><br>[$botInfo](../constructors/botInfo.md) = \['description' => [string](../types/string.md), 'commands' => \[[botCommand](../constructors/botCommand.md)\], \];<a name="botInfo"></a>  

***
<br><br>[$callbackQueryAnswer](../constructors/callbackQueryAnswer.md) = \['text' => [string](../types/string.md), 'show_alert' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), \];<a name="callbackQueryAnswer"></a>  

***
<br><br>[$callbackQueryData](../constructors/callbackQueryData.md) = \['data' => [bytes](../types/bytes.md), \];<a name="callbackQueryData"></a>  

***
<br><br>[$callbackQueryGame](../constructors/callbackQueryGame.md) = \['game_short_name' => [string](../types/string.md), \];<a name="callbackQueryGame"></a>  

***
<br><br>[$channel](../constructors/channel.md) = \['id' => [int](../types/int.md), 'username' => [string](../types/string.md), 'date' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'anyone_can_invite' => [Bool](../types/Bool.md), 'sign_messages' => [Bool](../types/Bool.md), 'is_supergroup' => [Bool](../types/Bool.md), 'is_verified' => [Bool](../types/Bool.md), 'restriction_reason' => [string](../types/string.md), \];<a name="channel"></a>  

***
<br><br>[$channelChatInfo](../constructors/channelChatInfo.md) = \['channel' => [channel](../constructors/channel.md), \];<a name="channelChatInfo"></a>  

***
<br><br>[$channelFull](../constructors/channelFull.md) = \['channel' => [channel](../constructors/channel.md), 'about' => [string](../types/string.md), 'member_count' => [int](../types/int.md), 'administrator_count' => [int](../types/int.md), 'kicked_count' => [int](../types/int.md), 'can_get_members' => [Bool](../types/Bool.md), 'can_set_username' => [Bool](../types/Bool.md), 'invite_link' => [string](../types/string.md), 'pinned_message_id' => [long](../types/long.md), 'migrated_from_group_id' => [int](../types/int.md), 'migrated_from_max_message_id' => [long](../types/long.md), \];<a name="channelFull"></a>  

***
<br><br>[$channelMembersAdministrators](../constructors/channelMembersAdministrators.md) = \[\];<a name="channelMembersAdministrators"></a>  

***
<br><br>[$channelMembersBots](../constructors/channelMembersBots.md) = \[\];<a name="channelMembersBots"></a>  

***
<br><br>[$channelMembersKicked](../constructors/channelMembersKicked.md) = \[\];<a name="channelMembersKicked"></a>  

***
<br><br>[$channelMembersRecent](../constructors/channelMembersRecent.md) = \[\];<a name="channelMembersRecent"></a>  

***
<br><br>[$channels](../constructors/channels.md) = \['channel_ids' => \[[int](../types/int.md)\], \];<a name="channels"></a>  

***
<br><br>[$chat](../constructors/chat.md) = \['id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), 'top_message' => [message](../constructors/message.md), 'order' => [long](../types/long.md), 'unread_count' => [int](../types/int.md), 'last_read_inbox_message_id' => [long](../types/long.md), 'last_read_outbox_message_id' => [long](../types/long.md), 'notification_settings' => [notificationSettings](../constructors/notificationSettings.md), 'reply_markup_message_id' => [long](../types/long.md), 'draft_message' => [draftMessage](../constructors/draftMessage.md), 'type' => [ChatInfo](../types/ChatInfo.md), \];<a name="chat"></a>  

***
<br><br>[$chatInviteLink](../constructors/chatInviteLink.md) = \['invite_link' => [string](../types/string.md), \];<a name="chatInviteLink"></a>  

***
<br><br>[$chatInviteLinkInfo](../constructors/chatInviteLinkInfo.md) = \['chat_id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), 'member_count' => [int](../types/int.md), 'members' => \[[user](../constructors/user.md)\], 'is_group' => [Bool](../types/Bool.md), 'is_channel' => [Bool](../types/Bool.md), 'is_public_channel' => [Bool](../types/Bool.md), 'is_supergroup_channel' => [Bool](../types/Bool.md), \];<a name="chatInviteLinkInfo"></a>  

***
<br><br>[$chatMember](../constructors/chatMember.md) = \['user_id' => [int](../types/int.md), 'inviter_user_id' => [int](../types/int.md), 'join_date' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'bot_info' => [botInfo](../constructors/botInfo.md), \];<a name="chatMember"></a>  

***
<br><br>[$chatMemberStatusCreator](../constructors/chatMemberStatusCreator.md) = \[\];<a name="chatMemberStatusCreator"></a>  

***
<br><br>[$chatMemberStatusEditor](../constructors/chatMemberStatusEditor.md) = \[\];<a name="chatMemberStatusEditor"></a>  

***
<br><br>[$chatMemberStatusKicked](../constructors/chatMemberStatusKicked.md) = \[\];<a name="chatMemberStatusKicked"></a>  

***
<br><br>[$chatMemberStatusLeft](../constructors/chatMemberStatusLeft.md) = \[\];<a name="chatMemberStatusLeft"></a>  

***
<br><br>[$chatMemberStatusMember](../constructors/chatMemberStatusMember.md) = \[\];<a name="chatMemberStatusMember"></a>  

***
<br><br>[$chatMemberStatusModerator](../constructors/chatMemberStatusModerator.md) = \[\];<a name="chatMemberStatusModerator"></a>  

***
<br><br>[$chatMembers](../constructors/chatMembers.md) = \['total_count' => [int](../types/int.md), 'members' => \[[chatMember](../constructors/chatMember.md)\], \];<a name="chatMembers"></a>  

***
<br><br>[$chatPhoto](../constructors/chatPhoto.md) = \['small' => [file](../constructors/file.md), 'big' => [file](../constructors/file.md), \];<a name="chatPhoto"></a>  

***
<br><br>[$chatReportSpamState](../constructors/chatReportSpamState.md) = \['can_report_spam' => [Bool](../types/Bool.md), \];<a name="chatReportSpamState"></a>  

***
<br><br>[$chats](../constructors/chats.md) = \['chats' => \[[chat](../constructors/chat.md)\], \];<a name="chats"></a>  

***
<br><br>[$contact](../constructors/contact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];<a name="contact"></a>  

***
<br><br>[$deviceTokenSet](../constructors/deviceTokenSet.md) = \['tokens' => \[[DeviceToken](../types/DeviceToken.md)\], \];<a name="deviceTokenSet"></a>  

***
<br><br>[$document](../constructors/document.md) = \['file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'document' => [file](../constructors/file.md), \];<a name="document"></a>  

***
<br><br>[$draftMessage](../constructors/draftMessage.md) = \['reply_to_message_id' => [long](../types/long.md), 'input_message_text' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="draftMessage"></a>  

***
<br><br>[$error](../constructors/error.md) = \['code' => [int](../types/int.md), 'message' => [string](../types/string.md), \];<a name="error"></a>  

***
<br><br>[$file](../constructors/file.md) = \['id' => [int](../types/int.md), 'persistent_id' => [string](../types/string.md), 'size' => [int](../types/int.md), 'path' => [string](../types/string.md), \];<a name="file"></a>  

***
<br><br>[$game](../constructors/game.md) = \['id' => [long](../types/long.md), 'short_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'text' => [string](../types/string.md), 'text_entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'description' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'animation' => [animation](../constructors/animation.md), \];<a name="game"></a>  

***
<br><br>[$gameHighScore](../constructors/gameHighScore.md) = \['position' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];<a name="gameHighScore"></a>  

***
<br><br>[$gameHighScores](../constructors/gameHighScores.md) = \['scores' => \[[gameHighScore](../constructors/gameHighScore.md)\], \];<a name="gameHighScores"></a>  

***
<br><br>[$gcmDeviceToken](../constructors/gcmDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="gcmDeviceToken"></a>  

***
<br><br>[$group](../constructors/group.md) = \['id' => [int](../types/int.md), 'member_count' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'anyone_can_edit' => [Bool](../types/Bool.md), 'is_active' => [Bool](../types/Bool.md), 'migrated_to_channel_id' => [int](../types/int.md), \];<a name="group"></a>  

***
<br><br>[$groupChatInfo](../constructors/groupChatInfo.md) = \['group' => [group](../constructors/group.md), \];<a name="groupChatInfo"></a>  

***
<br><br>[$groupFull](../constructors/groupFull.md) = \['group' => [group](../constructors/group.md), 'creator_user_id' => [int](../types/int.md), 'members' => \[[chatMember](../constructors/chatMember.md)\], 'invite_link' => [string](../types/string.md), \];<a name="groupFull"></a>  

***
<br><br>[$inlineKeyboardButton](../constructors/inlineKeyboardButton.md) = \['text' => [string](../types/string.md), 'type' => [InlineKeyboardButtonType](../types/InlineKeyboardButtonType.md), \];<a name="inlineKeyboardButton"></a>  

***
<br><br>[$inlineKeyboardButtonTypeCallback](../constructors/inlineKeyboardButtonTypeCallback.md) = \['data' => [bytes](../types/bytes.md), \];<a name="inlineKeyboardButtonTypeCallback"></a>  

***
<br><br>[$inlineKeyboardButtonTypeCallbackGame](../constructors/inlineKeyboardButtonTypeCallbackGame.md) = \[\];<a name="inlineKeyboardButtonTypeCallbackGame"></a>  

***
<br><br>[$inlineKeyboardButtonTypeSwitchInline](../constructors/inlineKeyboardButtonTypeSwitchInline.md) = \['query' => [string](../types/string.md), 'in_current_chat' => [Bool](../types/Bool.md), \];<a name="inlineKeyboardButtonTypeSwitchInline"></a>  

***
<br><br>[$inlineKeyboardButtonTypeUrl](../constructors/inlineKeyboardButtonTypeUrl.md) = \['url' => [string](../types/string.md), \];<a name="inlineKeyboardButtonTypeUrl"></a>  

***
<br><br>[$inlineQueryResultAnimation](../constructors/inlineQueryResultAnimation.md) = \['id' => [string](../types/string.md), 'animation' => [animation](../constructors/animation.md), 'title' => [string](../types/string.md), \];<a name="inlineQueryResultAnimation"></a>  

***
<br><br>[$inlineQueryResultArticle](../constructors/inlineQueryResultArticle.md) = \['id' => [string](../types/string.md), 'url' => [string](../types/string.md), 'hide_url' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), \];<a name="inlineQueryResultArticle"></a>  

***
<br><br>[$inlineQueryResultAudio](../constructors/inlineQueryResultAudio.md) = \['id' => [string](../types/string.md), 'audio' => [audio](../constructors/audio.md), \];<a name="inlineQueryResultAudio"></a>  

***
<br><br>[$inlineQueryResultContact](../constructors/inlineQueryResultContact.md) = \['id' => [string](../types/string.md), 'contact' => [contact](../constructors/contact.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), \];<a name="inlineQueryResultContact"></a>  

***
<br><br>[$inlineQueryResultDocument](../constructors/inlineQueryResultDocument.md) = \['id' => [string](../types/string.md), 'document' => [document](../constructors/document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultDocument"></a>  

***
<br><br>[$inlineQueryResultGame](../constructors/inlineQueryResultGame.md) = \['id' => [string](../types/string.md), 'game' => [game](../constructors/game.md), \];<a name="inlineQueryResultGame"></a>  

***
<br><br>[$inlineQueryResultLocation](../constructors/inlineQueryResultLocation.md) = \['id' => [string](../types/string.md), 'location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), \];<a name="inlineQueryResultLocation"></a>  

***
<br><br>[$inlineQueryResultPhoto](../constructors/inlineQueryResultPhoto.md) = \['id' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultPhoto"></a>  

***
<br><br>[$inlineQueryResultSticker](../constructors/inlineQueryResultSticker.md) = \['id' => [string](../types/string.md), 'sticker' => [sticker](../constructors/sticker.md), \];<a name="inlineQueryResultSticker"></a>  

***
<br><br>[$inlineQueryResultVenue](../constructors/inlineQueryResultVenue.md) = \['id' => [string](../types/string.md), 'venue' => [venue](../constructors/venue.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), \];<a name="inlineQueryResultVenue"></a>  

***
<br><br>[$inlineQueryResultVideo](../constructors/inlineQueryResultVideo.md) = \['id' => [string](../types/string.md), 'video' => [video](../constructors/video.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultVideo"></a>  

***
<br><br>[$inlineQueryResultVoice](../constructors/inlineQueryResultVoice.md) = \['id' => [string](../types/string.md), 'voice' => [voice](../constructors/voice.md), 'title' => [string](../types/string.md), \];<a name="inlineQueryResultVoice"></a>  

***
<br><br>[$inlineQueryResults](../constructors/inlineQueryResults.md) = \['inline_query_id' => [long](../types/long.md), 'next_offset' => [string](../types/string.md), 'results' => \[[InlineQueryResult](../types/InlineQueryResult.md)\], 'switch_pm_text' => [string](../types/string.md), 'switch_pm_parameter' => [string](../types/string.md), \];<a name="inlineQueryResults"></a>  

***
<br><br>[$inputFileGenerated](../constructors/inputFileGenerated.md) = \['original_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), 'expected_size' => [int](../types/int.md), 'should_cache' => [Bool](../types/Bool.md), \];<a name="inputFileGenerated"></a>  

***
<br><br>[$inputFileId](../constructors/inputFileId.md) = \['id' => [int](../types/int.md), \];<a name="inputFileId"></a>  

***
<br><br>[$inputFileLocal](../constructors/inputFileLocal.md) = \['path' => [string](../types/string.md), \];<a name="inputFileLocal"></a>  

***
<br><br>[$inputFilePersistentId](../constructors/inputFilePersistentId.md) = \['persistent_id' => [string](../types/string.md), \];<a name="inputFilePersistentId"></a>  

***
<br><br>[$inputInlineQueryResultAnimatedGif](../constructors/inputInlineQueryResultAnimatedGif.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'gif_url' => [string](../types/string.md), 'gif_width' => [int](../types/int.md), 'gif_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAnimatedGif"></a>  

***
<br><br>[$inputInlineQueryResultAnimatedMpeg4](../constructors/inputInlineQueryResultAnimatedMpeg4.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'mpeg4_url' => [string](../types/string.md), 'mpeg4_width' => [int](../types/int.md), 'mpeg4_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAnimatedMpeg4"></a>  

***
<br><br>[$inputInlineQueryResultArticle](../constructors/inputInlineQueryResultArticle.md) = \['id' => [string](../types/string.md), 'url' => [string](../types/string.md), 'hide_url' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultArticle"></a>  

***
<br><br>[$inputInlineQueryResultAudio](../constructors/inputInlineQueryResultAudio.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'audio_url' => [string](../types/string.md), 'audio_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAudio"></a>  

***
<br><br>[$inputInlineQueryResultContact](../constructors/inputInlineQueryResultContact.md) = \['id' => [string](../types/string.md), 'contact' => [contact](../constructors/contact.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultContact"></a>  

***
<br><br>[$inputInlineQueryResultDocument](../constructors/inputInlineQueryResultDocument.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document_url' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultDocument"></a>  

***
<br><br>[$inputInlineQueryResultGame](../constructors/inputInlineQueryResultGame.md) = \['id' => [string](../types/string.md), 'game_short_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputInlineQueryResultGame"></a>  

***
<br><br>[$inputInlineQueryResultLocation](../constructors/inputInlineQueryResultLocation.md) = \['id' => [string](../types/string.md), 'location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultLocation"></a>  

***
<br><br>[$inputInlineQueryResultPhoto](../constructors/inputInlineQueryResultPhoto.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'photo_url' => [string](../types/string.md), 'photo_width' => [int](../types/int.md), 'photo_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultPhoto"></a>  

***
<br><br>[$inputInlineQueryResultSticker](../constructors/inputInlineQueryResultSticker.md) = \['id' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'sticker_url' => [string](../types/string.md), 'sticker_width' => [int](../types/int.md), 'sticker_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultSticker"></a>  

***
<br><br>[$inputInlineQueryResultVenue](../constructors/inputInlineQueryResultVenue.md) = \['id' => [string](../types/string.md), 'venue' => [venue](../constructors/venue.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVenue"></a>  

***
<br><br>[$inputInlineQueryResultVideo](../constructors/inputInlineQueryResultVideo.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'video_url' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'video_width' => [int](../types/int.md), 'video_height' => [int](../types/int.md), 'video_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVideo"></a>  

***
<br><br>[$inputInlineQueryResultVoice](../constructors/inputInlineQueryResultVoice.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'voice_url' => [string](../types/string.md), 'voice_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVoice"></a>  

***
<br><br>[$inputMessageAnimation](../constructors/inputMessageAnimation.md) = \['animation' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageAnimation"></a>  

***
<br><br>[$inputMessageAudio](../constructors/inputMessageAudio.md) = \['audio' => [InputFile](../types/InputFile.md), 'album_cover_thumb' => [InputThumb](../types/InputThumb.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageAudio"></a>  

***
<br><br>[$inputMessageContact](../constructors/inputMessageContact.md) = \['contact' => [contact](../constructors/contact.md), \];<a name="inputMessageContact"></a>  

***
<br><br>[$inputMessageDocument](../constructors/inputMessageDocument.md) = \['document' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageDocument"></a>  

***
<br><br>[$inputMessageForwarded](../constructors/inputMessageForwarded.md) = \['from_chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'in_game_share' => [Bool](../types/Bool.md), \];<a name="inputMessageForwarded"></a>  

***
<br><br>[$inputMessageGame](../constructors/inputMessageGame.md) = \['bot_user_id' => [int](../types/int.md), 'game_short_name' => [string](../types/string.md), \];<a name="inputMessageGame"></a>  

***
<br><br>[$inputMessageLocation](../constructors/inputMessageLocation.md) = \['location' => [location](../constructors/location.md), \];<a name="inputMessageLocation"></a>  

***
<br><br>[$inputMessagePhoto](../constructors/inputMessagePhoto.md) = \['photo' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'added_sticker_file_ids' => \[[int](../types/int.md)\], 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), \];<a name="inputMessagePhoto"></a>  

***
<br><br>[$inputMessageSticker](../constructors/inputMessageSticker.md) = \['sticker' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputMessageSticker"></a>  

***
<br><br>[$inputMessageText](../constructors/inputMessageText.md) = \['text' => [string](../types/string.md), 'disable_web_page_preview' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'parse_mode' => [TextParseMode](../types/TextParseMode.md), \];<a name="inputMessageText"></a>  

***
<br><br>[$inputMessageVenue](../constructors/inputMessageVenue.md) = \['venue' => [venue](../constructors/venue.md), \];<a name="inputMessageVenue"></a>  

***
<br><br>[$inputMessageVideo](../constructors/inputMessageVideo.md) = \['video' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'added_sticker_file_ids' => \[[int](../types/int.md)\], 'duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageVideo"></a>  

***
<br><br>[$inputMessageVoice](../constructors/inputMessageVoice.md) = \['voice' => [InputFile](../types/InputFile.md), 'duration' => [int](../types/int.md), 'waveform' => [bytes](../types/bytes.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageVoice"></a>  

***
<br><br>[$inputThumbGenerated](../constructors/inputThumbGenerated.md) = \['original_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputThumbGenerated"></a>  

***
<br><br>[$inputThumbLocal](../constructors/inputThumbLocal.md) = \['path' => [string](../types/string.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputThumbLocal"></a>  

***
<br><br>[$keyboardButton](../constructors/keyboardButton.md) = \['text' => [string](../types/string.md), 'type' => [KeyboardButtonType](../types/KeyboardButtonType.md), \];<a name="keyboardButton"></a>  

***
<br><br>[$keyboardButtonTypeRequestLocation](../constructors/keyboardButtonTypeRequestLocation.md) = \[\];<a name="keyboardButtonTypeRequestLocation"></a>  

***
<br><br>[$keyboardButtonTypeRequestPhoneNumber](../constructors/keyboardButtonTypeRequestPhoneNumber.md) = \[\];<a name="keyboardButtonTypeRequestPhoneNumber"></a>  

***
<br><br>[$keyboardButtonTypeText](../constructors/keyboardButtonTypeText.md) = \[\];<a name="keyboardButtonTypeText"></a>  

***
<br><br>[$linkStateContact](../constructors/linkStateContact.md) = \[\];<a name="linkStateContact"></a>  

***
<br><br>[$linkStateKnowsPhoneNumber](../constructors/linkStateKnowsPhoneNumber.md) = \[\];<a name="linkStateKnowsPhoneNumber"></a>  

***
<br><br>[$linkStateNone](../constructors/linkStateNone.md) = \[\];<a name="linkStateNone"></a>  

***
<br><br>[$location](../constructors/location.md) = \['latitude' => [double](../types/double.md), 'longitude' => [double](../types/double.md), \];<a name="location"></a>  

***
<br><br>[$maskPosition](../constructors/maskPosition.md) = \['point' => [int](../types/int.md), 'x_shift' => [double](../types/double.md), 'y_shift' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \];<a name="maskPosition"></a>  

***
<br><br>[$message](../constructors/message.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'chat_id' => [long](../types/long.md), 'send_state' => [MessageSendState](../types/MessageSendState.md), 'can_be_edited' => [Bool](../types/Bool.md), 'can_be_deleted' => [Bool](../types/Bool.md), 'is_post' => [Bool](../types/Bool.md), 'date' => [int](../types/int.md), 'edit_date' => [int](../types/int.md), 'forward_info' => [MessageForwardInfo](../types/MessageForwardInfo.md), 'reply_to_message_id' => [long](../types/long.md), 'ttl' => [int](../types/int.md), 'ttl_expires_in' => [double](../types/double.md), 'via_bot_user_id' => [int](../types/int.md), 'views' => [int](../types/int.md), 'content' => [MessageContent](../types/MessageContent.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="message"></a>  

***
<br><br>[$messageAnimation](../constructors/messageAnimation.md) = \['animation' => [animation](../constructors/animation.md), 'caption' => [string](../types/string.md), \];<a name="messageAnimation"></a>  

***
<br><br>[$messageAudio](../constructors/messageAudio.md) = \['audio' => [audio](../constructors/audio.md), 'caption' => [string](../types/string.md), 'is_listened' => [Bool](../types/Bool.md), \];<a name="messageAudio"></a>  

***
<br><br>[$messageChannelChatCreate](../constructors/messageChannelChatCreate.md) = \['title' => [string](../types/string.md), \];<a name="messageChannelChatCreate"></a>  

***
<br><br>[$messageChatAddMembers](../constructors/messageChatAddMembers.md) = \['members' => \[[user](../constructors/user.md)\], \];<a name="messageChatAddMembers"></a>  

***
<br><br>[$messageChatChangePhoto](../constructors/messageChatChangePhoto.md) = \['photo' => [photo](../constructors/photo.md), \];<a name="messageChatChangePhoto"></a>  

***
<br><br>[$messageChatChangeTitle](../constructors/messageChatChangeTitle.md) = \['title' => [string](../types/string.md), \];<a name="messageChatChangeTitle"></a>  

***
<br><br>[$messageChatDeleteMember](../constructors/messageChatDeleteMember.md) = \['user' => [user](../constructors/user.md), \];<a name="messageChatDeleteMember"></a>  

***
<br><br>[$messageChatDeletePhoto](../constructors/messageChatDeletePhoto.md) = \[\];<a name="messageChatDeletePhoto"></a>  

***
<br><br>[$messageChatJoinByLink](../constructors/messageChatJoinByLink.md) = \[\];<a name="messageChatJoinByLink"></a>  

***
<br><br>[$messageChatMigrateFrom](../constructors/messageChatMigrateFrom.md) = \['title' => [string](../types/string.md), 'group_id' => [int](../types/int.md), \];<a name="messageChatMigrateFrom"></a>  

***
<br><br>[$messageChatMigrateTo](../constructors/messageChatMigrateTo.md) = \['channel_id' => [int](../types/int.md), \];<a name="messageChatMigrateTo"></a>  

***
<br><br>[$messageChatSetTtl](../constructors/messageChatSetTtl.md) = \['ttl' => [int](../types/int.md), \];<a name="messageChatSetTtl"></a>  

***
<br><br>[$messageContact](../constructors/messageContact.md) = \['contact' => [contact](../constructors/contact.md), \];<a name="messageContact"></a>  

***
<br><br>[$messageDocument](../constructors/messageDocument.md) = \['document' => [document](../constructors/document.md), 'caption' => [string](../types/string.md), \];<a name="messageDocument"></a>  

***
<br><br>[$messageEntityBold](../constructors/messageEntityBold.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityBold"></a>  

***
<br><br>[$messageEntityBotCommand](../constructors/messageEntityBotCommand.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityBotCommand"></a>  

***
<br><br>[$messageEntityCode](../constructors/messageEntityCode.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityCode"></a>  

***
<br><br>[$messageEntityEmail](../constructors/messageEntityEmail.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityEmail"></a>  

***
<br><br>[$messageEntityHashtag](../constructors/messageEntityHashtag.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityHashtag"></a>  

***
<br><br>[$messageEntityItalic](../constructors/messageEntityItalic.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityItalic"></a>  

***
<br><br>[$messageEntityMention](../constructors/messageEntityMention.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityMention"></a>  

***
<br><br>[$messageEntityMentionName](../constructors/messageEntityMentionName.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [int](../types/int.md), \];<a name="messageEntityMentionName"></a>  

***
<br><br>[$messageEntityPre](../constructors/messageEntityPre.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityPre"></a>  

***
<br><br>[$messageEntityPreCode](../constructors/messageEntityPreCode.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];<a name="messageEntityPreCode"></a>  

***
<br><br>[$messageEntityTextUrl](../constructors/messageEntityTextUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];<a name="messageEntityTextUrl"></a>  

***
<br><br>[$messageEntityUrl](../constructors/messageEntityUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityUrl"></a>  

***
<br><br>[$messageForwardedFromUser](../constructors/messageForwardedFromUser.md) = \['sender_user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="messageForwardedFromUser"></a>  

***
<br><br>[$messageForwardedPost](../constructors/messageForwardedPost.md) = \['chat_id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message_id' => [long](../types/long.md), \];<a name="messageForwardedPost"></a>  

***
<br><br>[$messageGame](../constructors/messageGame.md) = \['game' => [game](../constructors/game.md), \];<a name="messageGame"></a>  

***
<br><br>[$messageGameScore](../constructors/messageGameScore.md) = \['game_message_id' => [long](../types/long.md), 'game_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];<a name="messageGameScore"></a>  

***
<br><br>[$messageGroupChatCreate](../constructors/messageGroupChatCreate.md) = \['title' => [string](../types/string.md), 'members' => \[[user](../constructors/user.md)\], \];<a name="messageGroupChatCreate"></a>  

***
<br><br>[$messageIsBeingSent](../constructors/messageIsBeingSent.md) = \[\];<a name="messageIsBeingSent"></a>  

***
<br><br>[$messageIsFailedToSend](../constructors/messageIsFailedToSend.md) = \[\];<a name="messageIsFailedToSend"></a>  

***
<br><br>[$messageIsIncoming](../constructors/messageIsIncoming.md) = \[\];<a name="messageIsIncoming"></a>  

***
<br><br>[$messageIsSuccessfullySent](../constructors/messageIsSuccessfullySent.md) = \[\];<a name="messageIsSuccessfullySent"></a>  

***
<br><br>[$messageLocation](../constructors/messageLocation.md) = \['location' => [location](../constructors/location.md), \];<a name="messageLocation"></a>  

***
<br><br>[$messagePhoto](../constructors/messagePhoto.md) = \['photo' => [photo](../constructors/photo.md), 'caption' => [string](../types/string.md), \];<a name="messagePhoto"></a>  

***
<br><br>[$messagePinMessage](../constructors/messagePinMessage.md) = \['message_id' => [long](../types/long.md), \];<a name="messagePinMessage"></a>  

***
<br><br>[$messageScreenshotTaken](../constructors/messageScreenshotTaken.md) = \[\];<a name="messageScreenshotTaken"></a>  

***
<br><br>[$messageSticker](../constructors/messageSticker.md) = \['sticker' => [sticker](../constructors/sticker.md), \];<a name="messageSticker"></a>  

***
<br><br>[$messageText](../constructors/messageText.md) = \['text' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'web_page' => [webPage](../constructors/webPage.md), \];<a name="messageText"></a>  

***
<br><br>[$messageUnsupported](../constructors/messageUnsupported.md) = \[\];<a name="messageUnsupported"></a>  

***
<br><br>[$messageVenue](../constructors/messageVenue.md) = \['venue' => [venue](../constructors/venue.md), \];<a name="messageVenue"></a>  

***
<br><br>[$messageVideo](../constructors/messageVideo.md) = \['video' => [video](../constructors/video.md), 'caption' => [string](../types/string.md), \];<a name="messageVideo"></a>  

***
<br><br>[$messageVoice](../constructors/messageVoice.md) = \['voice' => [voice](../constructors/voice.md), 'caption' => [string](../types/string.md), 'is_listened' => [Bool](../types/Bool.md), \];<a name="messageVoice"></a>  

***
<br><br>[$messages](../constructors/messages.md) = \['total_count' => [int](../types/int.md), 'messages' => \[[message](../constructors/message.md)\], \];<a name="messages"></a>  

***
<br><br>[$mpnsDeviceToken](../constructors/mpnsDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="mpnsDeviceToken"></a>  

***
<br><br>[$notificationSettings](../constructors/notificationSettings.md) = \['mute_for' => [int](../types/int.md), 'sound' => [string](../types/string.md), 'show_preview' => [Bool](../types/Bool.md), \];<a name="notificationSettings"></a>  

***
<br><br>[$notificationSettingsForAllChats](../constructors/notificationSettingsForAllChats.md) = \[\];<a name="notificationSettingsForAllChats"></a>  

***
<br><br>[$notificationSettingsForChat](../constructors/notificationSettingsForChat.md) = \['chat_id' => [long](../types/long.md), \];<a name="notificationSettingsForChat"></a>  

***
<br><br>[$notificationSettingsForGroupChats](../constructors/notificationSettingsForGroupChats.md) = \[\];<a name="notificationSettingsForGroupChats"></a>  

***
<br><br>[$notificationSettingsForPrivateChats](../constructors/notificationSettingsForPrivateChats.md) = \[\];<a name="notificationSettingsForPrivateChats"></a>  

***
<br><br>[$ok](../constructors/ok.md) = \[\];<a name="ok"></a>  

***
<br><br>[$optionBoolean](../constructors/optionBoolean.md) = \['value' => [Bool](../types/Bool.md), \];<a name="optionBoolean"></a>  

***
<br><br>[$optionEmpty](../constructors/optionEmpty.md) = \[\];<a name="optionEmpty"></a>  

***
<br><br>[$optionInteger](../constructors/optionInteger.md) = \['value' => [int](../types/int.md), \];<a name="optionInteger"></a>  

***
<br><br>[$optionString](../constructors/optionString.md) = \['value' => [string](../types/string.md), \];<a name="optionString"></a>  

***
<br><br>[$passwordRecoveryInfo](../constructors/passwordRecoveryInfo.md) = \['recovery_email_pattern' => [string](../types/string.md), \];<a name="passwordRecoveryInfo"></a>  

***
<br><br>[$passwordState](../constructors/passwordState.md) = \['has_password' => [Bool](../types/Bool.md), 'password_hint' => [string](../types/string.md), 'has_recovery_email' => [Bool](../types/Bool.md), 'unconfirmed_recovery_email_pattern' => [string](../types/string.md), \];<a name="passwordState"></a>  

***
<br><br>[$photo](../constructors/photo.md) = \['id' => [long](../types/long.md), 'has_stickers' => [Bool](../types/Bool.md), 'sizes' => \[[photoSize](../constructors/photoSize.md)\], \];<a name="photo"></a>  

***
<br><br>[$photoSize](../constructors/photoSize.md) = \['type' => [string](../types/string.md), 'photo' => [file](../constructors/file.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="photoSize"></a>  

***
<br><br>[$privacyKeyChatInvite](../constructors/privacyKeyChatInvite.md) = \[\];<a name="privacyKeyChatInvite"></a>  

***
<br><br>[$privacyKeyUserStatus](../constructors/privacyKeyUserStatus.md) = \[\];<a name="privacyKeyUserStatus"></a>  

***
<br><br>[$privacyRuleAllowAll](../constructors/privacyRuleAllowAll.md) = \[\];<a name="privacyRuleAllowAll"></a>  

***
<br><br>[$privacyRuleAllowContacts](../constructors/privacyRuleAllowContacts.md) = \[\];<a name="privacyRuleAllowContacts"></a>  

***
<br><br>[$privacyRuleAllowUsers](../constructors/privacyRuleAllowUsers.md) = \['user_ids' => \[[int](../types/int.md)\], \];<a name="privacyRuleAllowUsers"></a>  

***
<br><br>[$privacyRuleDisallowAll](../constructors/privacyRuleDisallowAll.md) = \[\];<a name="privacyRuleDisallowAll"></a>  

***
<br><br>[$privacyRuleDisallowContacts](../constructors/privacyRuleDisallowContacts.md) = \[\];<a name="privacyRuleDisallowContacts"></a>  

***
<br><br>[$privacyRuleDisallowUsers](../constructors/privacyRuleDisallowUsers.md) = \['user_ids' => \[[int](../types/int.md)\], \];<a name="privacyRuleDisallowUsers"></a>  

***
<br><br>[$privacyRules](../constructors/privacyRules.md) = \['rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];<a name="privacyRules"></a>  

***
<br><br>[$privateChatInfo](../constructors/privateChatInfo.md) = \['user' => [user](../constructors/user.md), \];<a name="privateChatInfo"></a>  

***
<br><br>[$profilePhoto](../constructors/profilePhoto.md) = \['id' => [long](../types/long.md), 'small' => [file](../constructors/file.md), 'big' => [file](../constructors/file.md), \];<a name="profilePhoto"></a>  

***
<br><br>[$recoveryEmail](../constructors/recoveryEmail.md) = \['recovery_email' => [string](../types/string.md), \];<a name="recoveryEmail"></a>  

***
<br><br>[$replyMarkupForceReply](../constructors/replyMarkupForceReply.md) = \['personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupForceReply"></a>  

***
<br><br>[$replyMarkupInlineKeyboard](../constructors/replyMarkupInlineKeyboard.md) = \['rows' => \[[inlineKeyboardButton>](../constructors/inlineKeyboardButton>.md)\], \];<a name="replyMarkupInlineKeyboard"></a>  

***
<br><br>[$replyMarkupRemoveKeyboard](../constructors/replyMarkupRemoveKeyboard.md) = \['personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupRemoveKeyboard"></a>  

***
<br><br>[$replyMarkupShowKeyboard](../constructors/replyMarkupShowKeyboard.md) = \['rows' => \[[keyboardButton>](../constructors/keyboardButton>.md)\], 'resize_keyboard' => [Bool](../types/Bool.md), 'one_time' => [Bool](../types/Bool.md), 'personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupShowKeyboard"></a>  

***
<br><br>[$searchMessagesFilterAnimation](../constructors/searchMessagesFilterAnimation.md) = \[\];<a name="searchMessagesFilterAnimation"></a>  

***
<br><br>[$searchMessagesFilterAudio](../constructors/searchMessagesFilterAudio.md) = \[\];<a name="searchMessagesFilterAudio"></a>  

***
<br><br>[$searchMessagesFilterChatPhoto](../constructors/searchMessagesFilterChatPhoto.md) = \[\];<a name="searchMessagesFilterChatPhoto"></a>  

***
<br><br>[$searchMessagesFilterDocument](../constructors/searchMessagesFilterDocument.md) = \[\];<a name="searchMessagesFilterDocument"></a>  

***
<br><br>[$searchMessagesFilterEmpty](../constructors/searchMessagesFilterEmpty.md) = \[\];<a name="searchMessagesFilterEmpty"></a>  

***
<br><br>[$searchMessagesFilterPhoto](../constructors/searchMessagesFilterPhoto.md) = \[\];<a name="searchMessagesFilterPhoto"></a>  

***
<br><br>[$searchMessagesFilterPhotoAndVideo](../constructors/searchMessagesFilterPhotoAndVideo.md) = \[\];<a name="searchMessagesFilterPhotoAndVideo"></a>  

***
<br><br>[$searchMessagesFilterUrl](../constructors/searchMessagesFilterUrl.md) = \[\];<a name="searchMessagesFilterUrl"></a>  

***
<br><br>[$searchMessagesFilterVideo](../constructors/searchMessagesFilterVideo.md) = \[\];<a name="searchMessagesFilterVideo"></a>  

***
<br><br>[$searchMessagesFilterVoice](../constructors/searchMessagesFilterVoice.md) = \[\];<a name="searchMessagesFilterVoice"></a>  

***
<br><br>[$secretChat](../constructors/secretChat.md) = \['id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'state' => [int](../types/int.md), 'is_outbound' => [Bool](../types/Bool.md), 'ttl' => [int](../types/int.md), 'key_hash' => [bytes](../types/bytes.md), \];<a name="secretChat"></a>  

***
<br><br>[$secretChatInfo](../constructors/secretChatInfo.md) = \['secret_chat' => [secretChat](../constructors/secretChat.md), \];<a name="secretChatInfo"></a>  

***
<br><br>[$sendMessageCancelAction](../constructors/sendMessageCancelAction.md) = \[\];<a name="sendMessageCancelAction"></a>  

***
<br><br>[$sendMessageChooseContactAction](../constructors/sendMessageChooseContactAction.md) = \[\];<a name="sendMessageChooseContactAction"></a>  

***
<br><br>[$sendMessageGeoLocationAction](../constructors/sendMessageGeoLocationAction.md) = \[\];<a name="sendMessageGeoLocationAction"></a>  

***
<br><br>[$sendMessageRecordVideoAction](../constructors/sendMessageRecordVideoAction.md) = \[\];<a name="sendMessageRecordVideoAction"></a>  

***
<br><br>[$sendMessageRecordVoiceAction](../constructors/sendMessageRecordVoiceAction.md) = \[\];<a name="sendMessageRecordVoiceAction"></a>  

***
<br><br>[$sendMessageStartPlayGameAction](../constructors/sendMessageStartPlayGameAction.md) = \[\];<a name="sendMessageStartPlayGameAction"></a>  

***
<br><br>[$sendMessageTypingAction](../constructors/sendMessageTypingAction.md) = \[\];<a name="sendMessageTypingAction"></a>  

***
<br><br>[$sendMessageUploadDocumentAction](../constructors/sendMessageUploadDocumentAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadDocumentAction"></a>  

***
<br><br>[$sendMessageUploadPhotoAction](../constructors/sendMessageUploadPhotoAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadPhotoAction"></a>  

***
<br><br>[$sendMessageUploadVideoAction](../constructors/sendMessageUploadVideoAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadVideoAction"></a>  

***
<br><br>[$sendMessageUploadVoiceAction](../constructors/sendMessageUploadVoiceAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadVoiceAction"></a>  

***
<br><br>[$session](../constructors/session.md) = \['id' => [long](../types/long.md), 'is_current' => [Bool](../types/Bool.md), 'app_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'is_official_app' => [Bool](../types/Bool.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'date_created' => [int](../types/int.md), 'date_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];<a name="session"></a>  

***
<br><br>[$sessions](../constructors/sessions.md) = \['sessions' => \[[session](../constructors/session.md)\], \];<a name="sessions"></a>  

***
<br><br>[$simplePushDeviceToken](../constructors/simplePushDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="simplePushDeviceToken"></a>  

***
<br><br>[$sticker](../constructors/sticker.md) = \['set_id' => [long](../types/long.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'emoji' => [string](../types/string.md), 'is_mask' => [Bool](../types/Bool.md), 'mask_position' => [maskPosition](../constructors/maskPosition.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'sticker' => [file](../constructors/file.md), \];<a name="sticker"></a>  

***
<br><br>[$stickerEmojis](../constructors/stickerEmojis.md) = \['emojis' => \[[string](../types/string.md)\], \];<a name="stickerEmojis"></a>  

***
<br><br>[$stickerSet](../constructors/stickerSet.md) = \['id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'name' => [string](../types/string.md), 'is_installed' => [Bool](../types/Bool.md), 'is_archived' => [Bool](../types/Bool.md), 'is_official' => [Bool](../types/Bool.md), 'is_masks' => [Bool](../types/Bool.md), 'is_viewed' => [Bool](../types/Bool.md), 'stickers' => \[[sticker](../constructors/sticker.md)\], 'emojis' => \[[stickerEmojis](../constructors/stickerEmojis.md)\], \];<a name="stickerSet"></a>  

***
<br><br>[$stickerSetInfo](../constructors/stickerSetInfo.md) = \['id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'name' => [string](../types/string.md), 'is_installed' => [Bool](../types/Bool.md), 'is_archived' => [Bool](../types/Bool.md), 'is_official' => [Bool](../types/Bool.md), 'is_masks' => [Bool](../types/Bool.md), 'is_viewed' => [Bool](../types/Bool.md), 'size' => [int](../types/int.md), 'covers' => \[[sticker](../constructors/sticker.md)\], \];<a name="stickerSetInfo"></a>  

***
<br><br>[$stickerSets](../constructors/stickerSets.md) = \['total_count' => [int](../types/int.md), 'sets' => \[[stickerSetInfo](../constructors/stickerSetInfo.md)\], \];<a name="stickerSets"></a>  

***
<br><br>[$stickers](../constructors/stickers.md) = \['stickers' => \[[sticker](../constructors/sticker.md)\], \];<a name="stickers"></a>  

***
<br><br>[$test\_bytes](../constructors/test_bytes.md) = \['value' => [bytes](../types/bytes.md), \];<a name="test_bytes"></a>  

[$test\_empty](../constructors/test_empty.md) = \[\];<a name="test_empty"></a>  

[$test\_int](../constructors/test_int.md) = \['value' => [int](../types/int.md), \];<a name="test_int"></a>  

[$test\_string](../constructors/test_string.md) = \['value' => [string](../types/string.md), \];<a name="test_string"></a>  

[$test\_vectorInt](../constructors/test_vectorInt.md) = \['value' => \[[int](../types/int.md)\], \];<a name="test_vectorInt"></a>  

[$test\_vectorIntObject](../constructors/test_vectorIntObject.md) = \['value' => \[[test\_Int](../types/test_Int.md)\], \];<a name="test_vectorIntObject"></a>  

[$test\_vectorString](../constructors/test_vectorString.md) = \['value' => \[[string](../types/string.md)\], \];<a name="test_vectorString"></a>  

[$test\_vectorStringObject](../constructors/test_vectorStringObject.md) = \['value' => \[[test\_String](../types/test_String.md)\], \];<a name="test_vectorStringObject"></a>  

***
<br><br>[$textParseModeHTML](../constructors/textParseModeHTML.md) = \[\];<a name="textParseModeHTML"></a>  

***
<br><br>[$textParseModeMarkdown](../constructors/textParseModeMarkdown.md) = \[\];<a name="textParseModeMarkdown"></a>  

***
<br><br>[$ubuntuPhoneDeviceToken](../constructors/ubuntuPhoneDeviceToken.md) = \['token' => [string](../types/string.md), \];<a name="ubuntuPhoneDeviceToken"></a>  

***
<br><br>[$updateAuthState](../constructors/updateAuthState.md) = \['auth_state' => [AuthState](../types/AuthState.md), \];<a name="updateAuthState"></a>  

***
<br><br>[$updateChannel](../constructors/updateChannel.md) = \['channel' => [channel](../constructors/channel.md), \];<a name="updateChannel"></a>  

***
<br><br>[$updateChannelFull](../constructors/updateChannelFull.md) = \['channel_full' => [channelFull](../constructors/channelFull.md), \];<a name="updateChannelFull"></a>  

***
<br><br>[$updateChat](../constructors/updateChat.md) = \['chat' => [chat](../constructors/chat.md), \];<a name="updateChat"></a>  

***
<br><br>[$updateChatDraftMessage](../constructors/updateChatDraftMessage.md) = \['chat_id' => [long](../types/long.md), 'draft_message' => [draftMessage](../constructors/draftMessage.md), \];<a name="updateChatDraftMessage"></a>  

***
<br><br>[$updateChatOrder](../constructors/updateChatOrder.md) = \['chat_id' => [long](../types/long.md), 'order' => [long](../types/long.md), \];<a name="updateChatOrder"></a>  

***
<br><br>[$updateChatPhoto](../constructors/updateChatPhoto.md) = \['chat_id' => [long](../types/long.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), \];<a name="updateChatPhoto"></a>  

***
<br><br>[$updateChatReadInbox](../constructors/updateChatReadInbox.md) = \['chat_id' => [long](../types/long.md), 'last_read_inbox_message_id' => [long](../types/long.md), 'unread_count' => [int](../types/int.md), \];<a name="updateChatReadInbox"></a>  

***
<br><br>[$updateChatReadOutbox](../constructors/updateChatReadOutbox.md) = \['chat_id' => [long](../types/long.md), 'last_read_outbox_message_id' => [long](../types/long.md), \];<a name="updateChatReadOutbox"></a>  

***
<br><br>[$updateChatReplyMarkup](../constructors/updateChatReplyMarkup.md) = \['chat_id' => [long](../types/long.md), 'reply_markup_message_id' => [long](../types/long.md), \];<a name="updateChatReplyMarkup"></a>  

***
<br><br>[$updateChatTitle](../constructors/updateChatTitle.md) = \['chat_id' => [long](../types/long.md), 'title' => [string](../types/string.md), \];<a name="updateChatTitle"></a>  

***
<br><br>[$updateChatTopMessage](../constructors/updateChatTopMessage.md) = \['chat_id' => [long](../types/long.md), 'top_message' => [message](../constructors/message.md), \];<a name="updateChatTopMessage"></a>  

***
<br><br>[$updateDeleteMessages](../constructors/updateDeleteMessages.md) = \['chat_id' => [long](../types/long.md), 'message_ids' => \[[long](../types/long.md)\], \];<a name="updateDeleteMessages"></a>  

***
<br><br>[$updateFile](../constructors/updateFile.md) = \['file' => [file](../constructors/file.md), \];<a name="updateFile"></a>  

***
<br><br>[$updateFileGenerationFinish](../constructors/updateFileGenerationFinish.md) = \['file' => [file](../constructors/file.md), \];<a name="updateFileGenerationFinish"></a>  

***
<br><br>[$updateFileGenerationProgress](../constructors/updateFileGenerationProgress.md) = \['file_id' => [int](../types/int.md), 'size' => [int](../types/int.md), 'ready' => [int](../types/int.md), \];<a name="updateFileGenerationProgress"></a>  

***
<br><br>[$updateFileGenerationStart](../constructors/updateFileGenerationStart.md) = \['generation_id' => [long](../types/long.md), 'original_path' => [string](../types/string.md), 'destination_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), \];<a name="updateFileGenerationStart"></a>  

***
<br><br>[$updateFileProgress](../constructors/updateFileProgress.md) = \['file_id' => [int](../types/int.md), 'size' => [int](../types/int.md), 'ready' => [int](../types/int.md), \];<a name="updateFileProgress"></a>  

***
<br><br>[$updateGroup](../constructors/updateGroup.md) = \['group' => [group](../constructors/group.md), \];<a name="updateGroup"></a>  

***
<br><br>[$updateMessageContent](../constructors/updateMessageContent.md) = \['chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'new_content' => [MessageContent](../types/MessageContent.md), \];<a name="updateMessageContent"></a>  

***
<br><br>[$updateMessageEdited](../constructors/updateMessageEdited.md) = \['chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'edit_date' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="updateMessageEdited"></a>  

***
<br><br>[$updateMessageSendFailed](../constructors/updateMessageSendFailed.md) = \['chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'error_code' => [int](../types/int.md), 'error_message' => [string](../types/string.md), \];<a name="updateMessageSendFailed"></a>  

***
<br><br>[$updateMessageSendSucceeded](../constructors/updateMessageSendSucceeded.md) = \['message' => [message](../constructors/message.md), 'old_message_id' => [long](../types/long.md), \];<a name="updateMessageSendSucceeded"></a>  

***
<br><br>[$updateMessageViews](../constructors/updateMessageViews.md) = \['chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'views' => [int](../types/int.md), \];<a name="updateMessageViews"></a>  

***
<br><br>[$updateNewCallbackQuery](../constructors/updateNewCallbackQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'chat_id' => [long](../types/long.md), 'message_id' => [long](../types/long.md), 'chat_instance' => [long](../types/long.md), 'payload' => [CallbackQueryPayload](../types/CallbackQueryPayload.md), \];<a name="updateNewCallbackQuery"></a>  

***
<br><br>[$updateNewChosenInlineResult](../constructors/updateNewChosenInlineResult.md) = \['sender_user_id' => [int](../types/int.md), 'user_location' => [location](../constructors/location.md), 'query' => [string](../types/string.md), 'result_id' => [string](../types/string.md), 'inline_message_id' => [string](../types/string.md), \];<a name="updateNewChosenInlineResult"></a>  

***
<br><br>[$updateNewInlineCallbackQuery](../constructors/updateNewInlineCallbackQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'inline_message_id' => [string](../types/string.md), 'chat_instance' => [long](../types/long.md), 'payload' => [CallbackQueryPayload](../types/CallbackQueryPayload.md), \];<a name="updateNewInlineCallbackQuery"></a>  

***
<br><br>[$updateNewInlineQuery](../constructors/updateNewInlineQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'user_location' => [location](../constructors/location.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \];<a name="updateNewInlineQuery"></a>  

***
<br><br>[$updateNewMessage](../constructors/updateNewMessage.md) = \['message' => [message](../constructors/message.md), 'disable_notification' => [Bool](../types/Bool.md), \];<a name="updateNewMessage"></a>  

***
<br><br>[$updateNotificationSettings](../constructors/updateNotificationSettings.md) = \['scope' => [NotificationSettingsScope](../types/NotificationSettingsScope.md), 'notification_settings' => [notificationSettings](../constructors/notificationSettings.md), \];<a name="updateNotificationSettings"></a>  

***
<br><br>[$updateOption](../constructors/updateOption.md) = \['name' => [string](../types/string.md), 'value' => [OptionValue](../types/OptionValue.md), \];<a name="updateOption"></a>  

***
<br><br>[$updatePrivacy](../constructors/updatePrivacy.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => [privacyRules](../constructors/privacyRules.md), \];<a name="updatePrivacy"></a>  

***
<br><br>[$updateRecentStickers](../constructors/updateRecentStickers.md) = \['is_attached' => [Bool](../types/Bool.md), 'sticker_ids' => \[[int](../types/int.md)\], \];<a name="updateRecentStickers"></a>  

***
<br><br>[$updateSavedAnimations](../constructors/updateSavedAnimations.md) = \[\];<a name="updateSavedAnimations"></a>  

***
<br><br>[$updateSecretChat](../constructors/updateSecretChat.md) = \['secret_chat' => [secretChat](../constructors/secretChat.md), \];<a name="updateSecretChat"></a>  

***
<br><br>[$updateServiceNotification](../constructors/updateServiceNotification.md) = \['type' => [string](../types/string.md), 'content' => [MessageContent](../types/MessageContent.md), \];<a name="updateServiceNotification"></a>  

***
<br><br>[$updateStickerSets](../constructors/updateStickerSets.md) = \['is_masks' => [Bool](../types/Bool.md), 'sticker_set_ids' => \[[long](../types/long.md)\], \];<a name="updateStickerSets"></a>  

***
<br><br>[$updateTrendingStickerSets](../constructors/updateTrendingStickerSets.md) = \['sticker_sets' => [stickerSets](../constructors/stickerSets.md), \];<a name="updateTrendingStickerSets"></a>  

***
<br><br>[$updateUser](../constructors/updateUser.md) = \['user' => [user](../constructors/user.md), \];<a name="updateUser"></a>  

***
<br><br>[$updateUserAction](../constructors/updateUserAction.md) = \['chat_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];<a name="updateUserAction"></a>  

***
<br><br>[$updateUserBlocked](../constructors/updateUserBlocked.md) = \['user_id' => [int](../types/int.md), 'is_blocked' => [Bool](../types/Bool.md), \];<a name="updateUserBlocked"></a>  

***
<br><br>[$updateUserStatus](../constructors/updateUserStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];<a name="updateUserStatus"></a>  

***
<br><br>[$user](../constructors/user.md) = \['id' => [int](../types/int.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone_number' => [string](../types/string.md), 'status' => [UserStatus](../types/UserStatus.md), 'profile_photo' => [profilePhoto](../constructors/profilePhoto.md), 'my_link' => [LinkState](../types/LinkState.md), 'foreign_link' => [LinkState](../types/LinkState.md), 'is_verified' => [Bool](../types/Bool.md), 'restriction_reason' => [string](../types/string.md), 'have_access' => [Bool](../types/Bool.md), 'type' => [UserType](../types/UserType.md), \];<a name="user"></a>  

***
<br><br>[$userFull](../constructors/userFull.md) = \['user' => [user](../constructors/user.md), 'is_blocked' => [Bool](../types/Bool.md), 'about' => [string](../types/string.md), 'common_chat_count' => [int](../types/int.md), 'bot_info' => [botInfo](../constructors/botInfo.md), \];<a name="userFull"></a>  

***
<br><br>[$userProfilePhotos](../constructors/userProfilePhotos.md) = \['total_count' => [int](../types/int.md), 'photos' => \[[photo](../constructors/photo.md)\], \];<a name="userProfilePhotos"></a>  

***
<br><br>[$userStatusEmpty](../constructors/userStatusEmpty.md) = \[\];<a name="userStatusEmpty"></a>  

***
<br><br>[$userStatusLastMonth](../constructors/userStatusLastMonth.md) = \[\];<a name="userStatusLastMonth"></a>  

***
<br><br>[$userStatusLastWeek](../constructors/userStatusLastWeek.md) = \[\];<a name="userStatusLastWeek"></a>  

***
<br><br>[$userStatusOffline](../constructors/userStatusOffline.md) = \['was_online' => [int](../types/int.md), \];<a name="userStatusOffline"></a>  

***
<br><br>[$userStatusOnline](../constructors/userStatusOnline.md) = \['expires' => [int](../types/int.md), \];<a name="userStatusOnline"></a>  

***
<br><br>[$userStatusRecently](../constructors/userStatusRecently.md) = \[\];<a name="userStatusRecently"></a>  

***
<br><br>[$userTypeBot](../constructors/userTypeBot.md) = \['can_join_group_chats' => [Bool](../types/Bool.md), 'can_read_all_group_chat_messages' => [Bool](../types/Bool.md), 'is_inline' => [Bool](../types/Bool.md), 'inline_query_placeholder' => [string](../types/string.md), 'need_location' => [Bool](../types/Bool.md), \];<a name="userTypeBot"></a>  

***
<br><br>[$userTypeDeleted](../constructors/userTypeDeleted.md) = \[\];<a name="userTypeDeleted"></a>  

***
<br><br>[$userTypeGeneral](../constructors/userTypeGeneral.md) = \[\];<a name="userTypeGeneral"></a>  

***
<br><br>[$userTypeUnknown](../constructors/userTypeUnknown.md) = \[\];<a name="userTypeUnknown"></a>  

***
<br><br>[$users](../constructors/users.md) = \['total_count' => [int](../types/int.md), 'users' => \[[user](../constructors/user.md)\], \];<a name="users"></a>  

***
<br><br>[$venue](../constructors/venue.md) = \['location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'id' => [string](../types/string.md), \];<a name="venue"></a>  

***
<br><br>[$video](../constructors/video.md) = \['duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'has_stickers' => [Bool](../types/Bool.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'video' => [file](../constructors/file.md), \];<a name="video"></a>  

***
<br><br>[$voice](../constructors/voice.md) = \['duration' => [int](../types/int.md), 'waveform' => [bytes](../types/bytes.md), 'mime_type' => [string](../types/string.md), 'voice' => [file](../constructors/file.md), \];<a name="voice"></a>  

***
<br><br>[$wallpaper](../constructors/wallpaper.md) = \['sizes' => \[[photoSize](../constructors/photoSize.md)\], 'color' => [int](../types/int.md), \];<a name="wallpaper"></a>  

***
<br><br>[$wallpapers](../constructors/wallpapers.md) = \['wallpapers' => \[[wallpaper](../constructors/wallpaper.md)\], \];<a name="wallpapers"></a>  

***
<br><br>[$webPage](../constructors/webPage.md) = \['url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'animation' => [animation](../constructors/animation.md), 'audio' => [audio](../constructors/audio.md), 'document' => [document](../constructors/document.md), 'sticker' => [sticker](../constructors/sticker.md), 'video' => [video](../constructors/video.md), 'voice' => [voice](../constructors/voice.md), \];<a name="webPage"></a>  

