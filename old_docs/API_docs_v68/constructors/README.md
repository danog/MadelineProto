---
title: Constructors
description: List of constructors
---
# Constructors  
[Back to API documentation index](..)
***
<br><br>[$accountDaysTTL](../constructors/accountDaysTTL.md) = \['days' => [int](../types/int.md), \];<a name="accountDaysTTL"></a>  

***
<br><br>[$account\_authorizations](../constructors/account_authorizations.md) = \['authorizations' => \[[Authorization](../types/Authorization.md)\], \];<a name="account_authorizations"></a>  

[$account\_noPassword](../constructors/account_noPassword.md) = \['new_salt' => [bytes](../types/bytes.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];<a name="account_noPassword"></a>  

[$account\_password](../constructors/account_password.md) = \['current_salt' => [bytes](../types/bytes.md), 'new_salt' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'has_recovery' => [Bool](../types/Bool.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];<a name="account_password"></a>  

[$account\_passwordInputSettings](../constructors/account_passwordInputSettings.md) = \['new_salt' => [bytes](../types/bytes.md), 'new_password_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'email' => [string](../types/string.md), \];<a name="account_passwordInputSettings"></a>  

[$account\_passwordSettings](../constructors/account_passwordSettings.md) = \['email' => [string](../types/string.md), \];<a name="account_passwordSettings"></a>  

[$account\_privacyRules](../constructors/account_privacyRules.md) = \['rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="account_privacyRules"></a>  

[$account\_tmpPassword](../constructors/account_tmpPassword.md) = \['tmp_password' => [bytes](../types/bytes.md), 'valid_until' => [int](../types/int.md), \];<a name="account_tmpPassword"></a>  

***
<br><br>[$auth\_authorization](../constructors/auth_authorization.md) = \['tmp_sessions' => [int](../types/int.md), 'user' => [User](../types/User.md), \];<a name="auth_authorization"></a>  

[$auth\_checkedPhone](../constructors/auth_checkedPhone.md) = \['phone_registered' => [Bool](../types/Bool.md), \];<a name="auth_checkedPhone"></a>  

[$auth\_codeTypeCall](../constructors/auth_codeTypeCall.md) = \[\];<a name="auth_codeTypeCall"></a>  

[$auth\_codeTypeFlashCall](../constructors/auth_codeTypeFlashCall.md) = \[\];<a name="auth_codeTypeFlashCall"></a>  

[$auth\_codeTypeSms](../constructors/auth_codeTypeSms.md) = \[\];<a name="auth_codeTypeSms"></a>  

[$auth\_exportedAuthorization](../constructors/auth_exportedAuthorization.md) = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="auth_exportedAuthorization"></a>  

[$auth\_passwordRecovery](../constructors/auth_passwordRecovery.md) = \['email_pattern' => [string](../types/string.md), \];<a name="auth_passwordRecovery"></a>  

[$auth\_sentCode](../constructors/auth_sentCode.md) = \['phone_registered' => [Bool](../types/Bool.md), 'type' => [auth\_SentCodeType](../types/auth_SentCodeType.md), 'phone_code_hash' => [string](../types/string.md), 'next_type' => [auth\_CodeType](../types/auth_CodeType.md), 'timeout' => [int](../types/int.md), \];<a name="auth_sentCode"></a>  

[$auth\_sentCodeTypeApp](../constructors/auth_sentCodeTypeApp.md) = \['length' => [int](../types/int.md), \];<a name="auth_sentCodeTypeApp"></a>  

[$auth\_sentCodeTypeCall](../constructors/auth_sentCodeTypeCall.md) = \['length' => [int](../types/int.md), \];<a name="auth_sentCodeTypeCall"></a>  

[$auth\_sentCodeTypeFlashCall](../constructors/auth_sentCodeTypeFlashCall.md) = \['pattern' => [string](../types/string.md), \];<a name="auth_sentCodeTypeFlashCall"></a>  

[$auth\_sentCodeTypeSms](../constructors/auth_sentCodeTypeSms.md) = \['length' => [int](../types/int.md), \];<a name="auth_sentCodeTypeSms"></a>  

***
<br><br>[$authorization](../constructors/authorization.md) = \['hash' => [long](../types/long.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'api_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'date_created' => [int](../types/int.md), 'date_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];<a name="authorization"></a>  

***
<br><br>[$bad\_msg\_notification](../constructors/bad_msg_notification.md) = \['bad_msg_id' => [long](../types/long.md), 'bad_msg_seqno' => [int](../types/int.md), 'error_code' => [int](../types/int.md), \];<a name="bad_msg_notification"></a>  

[$bad\_server\_salt](../constructors/bad_server_salt.md) = \['bad_msg_id' => [long](../types/long.md), 'bad_msg_seqno' => [int](../types/int.md), 'error_code' => [int](../types/int.md), 'new_server_salt' => [long](../types/long.md), \];<a name="bad_server_salt"></a>  

***
<br><br>[$boolFalse](../constructors/boolFalse.md) = \[\];<a name="boolFalse"></a>  

***
<br><br>[$boolTrue](../constructors/boolTrue.md) = \[\];<a name="boolTrue"></a>  

***
<br><br>[$botCommand](../constructors/botCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="botCommand"></a>  

***
<br><br>[$botInfo](../constructors/botInfo.md) = \['user_id' => [int](../types/int.md), 'description' => [string](../types/string.md), 'commands' => \[[BotCommand](../types/BotCommand.md)\], \];<a name="botInfo"></a>  

***
<br><br>[$botInlineMediaResult](../constructors/botInlineMediaResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];<a name="botInlineMediaResult"></a>  

***
<br><br>[$botInlineMessageMediaAuto](../constructors/botInlineMessageMediaAuto.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="botInlineMessageMediaAuto"></a>  

***
<br><br>[$botInlineMessageMediaContact](../constructors/botInlineMessageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="botInlineMessageMediaContact"></a>  

***
<br><br>[$botInlineMessageMediaGeo](../constructors/botInlineMessageMediaGeo.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="botInlineMessageMediaGeo"></a>  

***
<br><br>[$botInlineMessageMediaVenue](../constructors/botInlineMessageMediaVenue.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="botInlineMessageMediaVenue"></a>  

***
<br><br>[$botInlineMessageText](../constructors/botInlineMessageText.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="botInlineMessageText"></a>  

***
<br><br>[$botInlineResult](../constructors/botInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];<a name="botInlineResult"></a>  

***
<br><br>[$cdnConfig](../constructors/cdnConfig.md) = \['public_keys' => \[[CdnPublicKey](../types/CdnPublicKey.md)\], \];<a name="cdnConfig"></a>  

***
<br><br>[$cdnPublicKey](../constructors/cdnPublicKey.md) = \['dc_id' => [int](../types/int.md), 'public_key' => [string](../types/string.md), \];<a name="cdnPublicKey"></a>  

***
<br><br>[$channel](../constructors/channel.md) = \['creator' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'democracy' => [Bool](../types/Bool.md), 'signatures' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), 'admin_rights' => [ChannelAdminRights](../types/ChannelAdminRights.md), 'banned_rights' => [ChannelBannedRights](../types/ChannelBannedRights.md), \];<a name="channel"></a>  

***
<br><br>[$channelAdminLogEvent](../constructors/channelAdminLogEvent.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'action' => [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md), \];<a name="channelAdminLogEvent"></a>  

***
<br><br>[$channelAdminLogEventActionChangeAbout](../constructors/channelAdminLogEventActionChangeAbout.md) = \['prev_value' => [string](../types/string.md), 'new_value' => [string](../types/string.md), \];<a name="channelAdminLogEventActionChangeAbout"></a>  

***
<br><br>[$channelAdminLogEventActionChangePhoto](../constructors/channelAdminLogEventActionChangePhoto.md) = \['prev_photo' => [ChatPhoto](../types/ChatPhoto.md), 'new_photo' => [ChatPhoto](../types/ChatPhoto.md), \];<a name="channelAdminLogEventActionChangePhoto"></a>  

***
<br><br>[$channelAdminLogEventActionChangeTitle](../constructors/channelAdminLogEventActionChangeTitle.md) = \['prev_value' => [string](../types/string.md), 'new_value' => [string](../types/string.md), \];<a name="channelAdminLogEventActionChangeTitle"></a>  

***
<br><br>[$channelAdminLogEventActionChangeUsername](../constructors/channelAdminLogEventActionChangeUsername.md) = \['prev_value' => [string](../types/string.md), 'new_value' => [string](../types/string.md), \];<a name="channelAdminLogEventActionChangeUsername"></a>  

***
<br><br>[$channelAdminLogEventActionDeleteMessage](../constructors/channelAdminLogEventActionDeleteMessage.md) = \['message' => [Message](../types/Message.md), \];<a name="channelAdminLogEventActionDeleteMessage"></a>  

***
<br><br>[$channelAdminLogEventActionEditMessage](../constructors/channelAdminLogEventActionEditMessage.md) = \['prev_message' => [Message](../types/Message.md), 'new_message' => [Message](../types/Message.md), \];<a name="channelAdminLogEventActionEditMessage"></a>  

***
<br><br>[$channelAdminLogEventActionParticipantInvite](../constructors/channelAdminLogEventActionParticipantInvite.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), \];<a name="channelAdminLogEventActionParticipantInvite"></a>  

***
<br><br>[$channelAdminLogEventActionParticipantJoin](../constructors/channelAdminLogEventActionParticipantJoin.md) = \[\];<a name="channelAdminLogEventActionParticipantJoin"></a>  

***
<br><br>[$channelAdminLogEventActionParticipantLeave](../constructors/channelAdminLogEventActionParticipantLeave.md) = \[\];<a name="channelAdminLogEventActionParticipantLeave"></a>  

***
<br><br>[$channelAdminLogEventActionParticipantToggleAdmin](../constructors/channelAdminLogEventActionParticipantToggleAdmin.md) = \['prev_participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'new_participant' => [ChannelParticipant](../types/ChannelParticipant.md), \];<a name="channelAdminLogEventActionParticipantToggleAdmin"></a>  

***
<br><br>[$channelAdminLogEventActionParticipantToggleBan](../constructors/channelAdminLogEventActionParticipantToggleBan.md) = \['prev_participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'new_participant' => [ChannelParticipant](../types/ChannelParticipant.md), \];<a name="channelAdminLogEventActionParticipantToggleBan"></a>  

***
<br><br>[$channelAdminLogEventActionToggleInvites](../constructors/channelAdminLogEventActionToggleInvites.md) = \['new_value' => [Bool](../types/Bool.md), \];<a name="channelAdminLogEventActionToggleInvites"></a>  

***
<br><br>[$channelAdminLogEventActionToggleSignatures](../constructors/channelAdminLogEventActionToggleSignatures.md) = \['new_value' => [Bool](../types/Bool.md), \];<a name="channelAdminLogEventActionToggleSignatures"></a>  

***
<br><br>[$channelAdminLogEventActionUpdatePinned](../constructors/channelAdminLogEventActionUpdatePinned.md) = \['message' => [Message](../types/Message.md), \];<a name="channelAdminLogEventActionUpdatePinned"></a>  

***
<br><br>[$channelAdminLogEventsFilter](../constructors/channelAdminLogEventsFilter.md) = \['join' => [Bool](../types/Bool.md), 'leave' => [Bool](../types/Bool.md), 'invite' => [Bool](../types/Bool.md), 'ban' => [Bool](../types/Bool.md), 'unban' => [Bool](../types/Bool.md), 'kick' => [Bool](../types/Bool.md), 'unkick' => [Bool](../types/Bool.md), 'promote' => [Bool](../types/Bool.md), 'demote' => [Bool](../types/Bool.md), 'info' => [Bool](../types/Bool.md), 'settings' => [Bool](../types/Bool.md), 'pinned' => [Bool](../types/Bool.md), 'edit' => [Bool](../types/Bool.md), 'delete' => [Bool](../types/Bool.md), \];<a name="channelAdminLogEventsFilter"></a>  

***
<br><br>[$channelAdminRights](../constructors/channelAdminRights.md) = \['change_info' => [Bool](../types/Bool.md), 'post_messages' => [Bool](../types/Bool.md), 'edit_messages' => [Bool](../types/Bool.md), 'delete_messages' => [Bool](../types/Bool.md), 'ban_users' => [Bool](../types/Bool.md), 'invite_users' => [Bool](../types/Bool.md), 'invite_link' => [Bool](../types/Bool.md), 'pin_messages' => [Bool](../types/Bool.md), 'add_admins' => [Bool](../types/Bool.md), \];<a name="channelAdminRights"></a>  

***
<br><br>[$channelBannedRights](../constructors/channelBannedRights.md) = \['view_messages' => [Bool](../types/Bool.md), 'send_messages' => [Bool](../types/Bool.md), 'send_media' => [Bool](../types/Bool.md), 'send_stickers' => [Bool](../types/Bool.md), 'send_gifs' => [Bool](../types/Bool.md), 'send_games' => [Bool](../types/Bool.md), 'send_inline' => [Bool](../types/Bool.md), 'embed_links' => [Bool](../types/Bool.md), 'until_date' => [int](../types/int.md), \];<a name="channelBannedRights"></a>  

***
<br><br>[$channelForbidden](../constructors/channelForbidden.md) = \['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'until_date' => [int](../types/int.md), \];<a name="channelForbidden"></a>  

***
<br><br>[$channelFull](../constructors/channelFull.md) = \['can_view_participants' => [Bool](../types/Bool.md), 'can_set_username' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants_count' => [int](../types/int.md), 'admins_count' => [int](../types/int.md), 'kicked_count' => [int](../types/int.md), 'banned_count' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], 'migrated_from_chat_id' => [int](../types/int.md), 'migrated_from_max_id' => [int](../types/int.md), 'pinned_msg_id' => [int](../types/int.md), \];<a name="channelFull"></a>  

***
<br><br>[$channelMessagesFilter](../constructors/channelMessagesFilter.md) = \['exclude_new_messages' => [Bool](../types/Bool.md), 'ranges' => \[[MessageRange](../types/MessageRange.md)\], \];<a name="channelMessagesFilter"></a>  

***
<br><br>[$channelMessagesFilterEmpty](../constructors/channelMessagesFilterEmpty.md) = \[\];<a name="channelMessagesFilterEmpty"></a>  

***
<br><br>[$channelParticipant](../constructors/channelParticipant.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipant"></a>  

***
<br><br>[$channelParticipantAdmin](../constructors/channelParticipantAdmin.md) = \['can_edit' => [Bool](../types/Bool.md), 'user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'promoted_by' => [int](../types/int.md), 'date' => [int](../types/int.md), 'admin_rights' => [ChannelAdminRights](../types/ChannelAdminRights.md), \];<a name="channelParticipantAdmin"></a>  

***
<br><br>[$channelParticipantBanned](../constructors/channelParticipantBanned.md) = \['left' => [Bool](../types/Bool.md), 'user_id' => [int](../types/int.md), 'kicked_by' => [int](../types/int.md), 'date' => [int](../types/int.md), 'banned_rights' => [ChannelBannedRights](../types/ChannelBannedRights.md), \];<a name="channelParticipantBanned"></a>  

***
<br><br>[$channelParticipantCreator](../constructors/channelParticipantCreator.md) = \['user_id' => [int](../types/int.md), \];<a name="channelParticipantCreator"></a>  

***
<br><br>[$channelParticipantSelf](../constructors/channelParticipantSelf.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipantSelf"></a>  

***
<br><br>[$channelParticipantsAdmins](../constructors/channelParticipantsAdmins.md) = \[\];<a name="channelParticipantsAdmins"></a>  

***
<br><br>[$channelParticipantsBanned](../constructors/channelParticipantsBanned.md) = \['q' => [string](../types/string.md), \];<a name="channelParticipantsBanned"></a>  

***
<br><br>[$channelParticipantsBots](../constructors/channelParticipantsBots.md) = \[\];<a name="channelParticipantsBots"></a>  

***
<br><br>[$channelParticipantsKicked](../constructors/channelParticipantsKicked.md) = \['q' => [string](../types/string.md), \];<a name="channelParticipantsKicked"></a>  

***
<br><br>[$channelParticipantsRecent](../constructors/channelParticipantsRecent.md) = \[\];<a name="channelParticipantsRecent"></a>  

***
<br><br>[$channelParticipantsSearch](../constructors/channelParticipantsSearch.md) = \['q' => [string](../types/string.md), \];<a name="channelParticipantsSearch"></a>  

***
<br><br>[$channels\_adminLogResults](../constructors/channels_adminLogResults.md) = \['events' => \[[ChannelAdminLogEvent](../types/ChannelAdminLogEvent.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="channels_adminLogResults"></a>  

[$channels\_channelParticipant](../constructors/channels_channelParticipant.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => \[[User](../types/User.md)\], \];<a name="channels_channelParticipant"></a>  

[$channels\_channelParticipants](../constructors/channels_channelParticipants.md) = \['count' => [int](../types/int.md), 'participants' => \[[ChannelParticipant](../types/ChannelParticipant.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="channels_channelParticipants"></a>  

***
<br><br>[$chat](../constructors/chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'admins_enabled' => [Bool](../types/Bool.md), 'admin' => [Bool](../types/Bool.md), 'deactivated' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'migrated_to' => [InputChannel](../types/InputChannel.md), \];<a name="chat"></a>  

***
<br><br>[$chatEmpty](../constructors/chatEmpty.md) = \['id' => [int](../types/int.md), \];<a name="chatEmpty"></a>  

***
<br><br>[$chatForbidden](../constructors/chatForbidden.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), \];<a name="chatForbidden"></a>  

***
<br><br>[$chatFull](../constructors/chatFull.md) = \['id' => [int](../types/int.md), 'participants' => [ChatParticipants](../types/ChatParticipants.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], \];<a name="chatFull"></a>  

***
<br><br>[$chatInvite](../constructors/chatInvite.md) = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'participants' => \[[User](../types/User.md)\], \];<a name="chatInvite"></a>  

***
<br><br>[$chatInviteAlready](../constructors/chatInviteAlready.md) = \['chat' => [Chat](../types/Chat.md), \];<a name="chatInviteAlready"></a>  

***
<br><br>[$chatInviteEmpty](../constructors/chatInviteEmpty.md) = \[\];<a name="chatInviteEmpty"></a>  

***
<br><br>[$chatInviteExported](../constructors/chatInviteExported.md) = \['link' => [string](../types/string.md), \];<a name="chatInviteExported"></a>  

***
<br><br>[$chatParticipant](../constructors/chatParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="chatParticipant"></a>  

***
<br><br>[$chatParticipantAdmin](../constructors/chatParticipantAdmin.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="chatParticipantAdmin"></a>  

***
<br><br>[$chatParticipantCreator](../constructors/chatParticipantCreator.md) = \['user_id' => [int](../types/int.md), \];<a name="chatParticipantCreator"></a>  

***
<br><br>[$chatParticipants](../constructors/chatParticipants.md) = \['chat_id' => [int](../types/int.md), 'participants' => \[[ChatParticipant](../types/ChatParticipant.md)\], 'version' => [int](../types/int.md), \];<a name="chatParticipants"></a>  

***
<br><br>[$chatParticipantsForbidden](../constructors/chatParticipantsForbidden.md) = \['chat_id' => [int](../types/int.md), 'self_participant' => [ChatParticipant](../types/ChatParticipant.md), \];<a name="chatParticipantsForbidden"></a>  

***
<br><br>[$chatPhoto](../constructors/chatPhoto.md) = \['photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];<a name="chatPhoto"></a>  

***
<br><br>[$chatPhotoEmpty](../constructors/chatPhotoEmpty.md) = \[\];<a name="chatPhotoEmpty"></a>  

***
<br><br>[$client\_DH\_inner\_data](../constructors/client_DH_inner_data.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'retry_id' => [long](../types/long.md), 'g_b' => [string](../types/string.md), \];<a name="client_DH_inner_data"></a>  

***
<br><br>[$config](../constructors/config.md) = \['phonecalls_enabled' => [Bool](../types/Bool.md), 'date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test_mode' => [Bool](../types/Bool.md), 'this_dc' => [int](../types/int.md), 'dc_options' => \[[DcOption](../types/DcOption.md)\], 'chat_size_max' => [int](../types/int.md), 'megagroup_size_max' => [int](../types/int.md), 'forwarded_count_max' => [int](../types/int.md), 'online_update_period_ms' => [int](../types/int.md), 'offline_blur_timeout_ms' => [int](../types/int.md), 'offline_idle_timeout_ms' => [int](../types/int.md), 'online_cloud_timeout_ms' => [int](../types/int.md), 'notify_cloud_delay_ms' => [int](../types/int.md), 'notify_default_delay_ms' => [int](../types/int.md), 'chat_big_size' => [int](../types/int.md), 'push_chat_period_ms' => [int](../types/int.md), 'push_chat_limit' => [int](../types/int.md), 'saved_gifs_limit' => [int](../types/int.md), 'edit_time_limit' => [int](../types/int.md), 'rating_e_decay' => [int](../types/int.md), 'stickers_recent_limit' => [int](../types/int.md), 'tmp_sessions' => [int](../types/int.md), 'pinned_dialogs_count_max' => [int](../types/int.md), 'call_receive_timeout_ms' => [int](../types/int.md), 'call_ring_timeout_ms' => [int](../types/int.md), 'call_connect_timeout_ms' => [int](../types/int.md), 'call_packet_timeout_ms' => [int](../types/int.md), 'me_url_prefix' => [string](../types/string.md), 'suggested_lang_code' => [string](../types/string.md), 'lang_pack_version' => [int](../types/int.md), 'disabled_features' => \[[DisabledFeature](../types/DisabledFeature.md)\], \];<a name="config"></a>  

***
<br><br>[$contact](../constructors/contact.md) = \['user_id' => [int](../types/int.md), 'mutual' => [Bool](../types/Bool.md), \];<a name="contact"></a>  

***
<br><br>[$contactBlocked](../constructors/contactBlocked.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="contactBlocked"></a>  

***
<br><br>[$contactLinkContact](../constructors/contactLinkContact.md) = \[\];<a name="contactLinkContact"></a>  

***
<br><br>[$contactLinkHasPhone](../constructors/contactLinkHasPhone.md) = \[\];<a name="contactLinkHasPhone"></a>  

***
<br><br>[$contactLinkNone](../constructors/contactLinkNone.md) = \[\];<a name="contactLinkNone"></a>  

***
<br><br>[$contactLinkUnknown](../constructors/contactLinkUnknown.md) = \[\];<a name="contactLinkUnknown"></a>  

***
<br><br>[$contactStatus](../constructors/contactStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];<a name="contactStatus"></a>  

***
<br><br>[$contacts\_blocked](../constructors/contacts_blocked.md) = \['blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_blocked"></a>  

[$contacts\_blockedSlice](../constructors/contacts_blockedSlice.md) = \['count' => [int](../types/int.md), 'blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_blockedSlice"></a>  

[$contacts\_contacts](../constructors/contacts_contacts.md) = \['contacts' => \[[Contact](../types/Contact.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_contacts"></a>  

[$contacts\_contactsNotModified](../constructors/contacts_contactsNotModified.md) = \[\];<a name="contacts_contactsNotModified"></a>  

[$contacts\_found](../constructors/contacts_found.md) = \['results' => \[[Peer](../types/Peer.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_found"></a>  

[$contacts\_importedContacts](../constructors/contacts_importedContacts.md) = \['imported' => \[[ImportedContact](../types/ImportedContact.md)\], 'retry_contacts' => \[[long](../types/long.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_importedContacts"></a>  

[$contacts\_link](../constructors/contacts_link.md) = \['my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \];<a name="contacts_link"></a>  

[$contacts\_resolvedPeer](../constructors/contacts_resolvedPeer.md) = \['peer' => [Peer](../types/Peer.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_resolvedPeer"></a>  

[$contacts\_topPeers](../constructors/contacts_topPeers.md) = \['categories' => \[[TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_topPeers"></a>  

[$contacts\_topPeersNotModified](../constructors/contacts_topPeersNotModified.md) = \[\];<a name="contacts_topPeersNotModified"></a>  

***
<br><br>[$dataJSON](../constructors/dataJSON.md) = \['data' => [string](../types/string.md), \];<a name="dataJSON"></a>  

***
<br><br>[$dcOption](../constructors/dcOption.md) = \['ipv6' => [Bool](../types/Bool.md), 'media_only' => [Bool](../types/Bool.md), 'tcpo_only' => [Bool](../types/Bool.md), 'cdn' => [Bool](../types/Bool.md), 'static' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \];<a name="dcOption"></a>  

***
<br><br>[$destroy\_auth\_key\_fail](../constructors/destroy_auth_key_fail.md) = \[\];<a name="destroy_auth_key_fail"></a>  

[$destroy\_auth\_key\_none](../constructors/destroy_auth_key_none.md) = \[\];<a name="destroy_auth_key_none"></a>  

[$destroy\_auth\_key\_ok](../constructors/destroy_auth_key_ok.md) = \[\];<a name="destroy_auth_key_ok"></a>  

[$destroy\_session\_none](../constructors/destroy_session_none.md) = \['session_id' => [long](../types/long.md), \];<a name="destroy_session_none"></a>  

[$destroy\_session\_ok](../constructors/destroy_session_ok.md) = \['session_id' => [long](../types/long.md), \];<a name="destroy_session_ok"></a>  

***
<br><br>[$dh\_gen\_fail](../constructors/dh_gen_fail.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash3' => [int128](../types/int128.md), \];<a name="dh_gen_fail"></a>  

[$dh\_gen\_ok](../constructors/dh_gen_ok.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash1' => [int128](../types/int128.md), \];<a name="dh_gen_ok"></a>  

[$dh\_gen\_retry](../constructors/dh_gen_retry.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash2' => [int128](../types/int128.md), \];<a name="dh_gen_retry"></a>  

***
<br><br>[$dialog](../constructors/dialog.md) = \['pinned' => [Bool](../types/Bool.md), 'peer' => [Peer](../types/Peer.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];<a name="dialog"></a>  

***
<br><br>[$disabledFeature](../constructors/disabledFeature.md) = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="disabledFeature"></a>  

***
<br><br>[$document](../constructors/document.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];<a name="document"></a>  

***
<br><br>[$documentAttributeAnimated](../constructors/documentAttributeAnimated.md) = \[\];<a name="documentAttributeAnimated"></a>  

***
<br><br>[$documentAttributeAudio](../constructors/documentAttributeAudio.md) = \['voice' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'waveform' => [bytes](../types/bytes.md), \];<a name="documentAttributeAudio"></a>  

***
<br><br>[$documentAttributeFilename](../constructors/documentAttributeFilename.md) = \['file_name' => [string](../types/string.md), \];<a name="documentAttributeFilename"></a>  

***
<br><br>[$documentAttributeHasStickers](../constructors/documentAttributeHasStickers.md) = \[\];<a name="documentAttributeHasStickers"></a>  

***
<br><br>[$documentAttributeImageSize](../constructors/documentAttributeImageSize.md) = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="documentAttributeImageSize"></a>  

***
<br><br>[$documentAttributeSticker](../constructors/documentAttributeSticker.md) = \['mask' => [Bool](../types/Bool.md), 'alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'mask_coords' => [MaskCoords](../types/MaskCoords.md), \];<a name="documentAttributeSticker"></a>  

***
<br><br>[$documentAttributeVideo](../constructors/documentAttributeVideo.md) = \['round_message' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="documentAttributeVideo"></a>  

***
<br><br>[$documentEmpty](../constructors/documentEmpty.md) = \['id' => [long](../types/long.md), \];<a name="documentEmpty"></a>  

***
<br><br>[$draftMessage](../constructors/draftMessage.md) = \['no_webpage' => [Bool](../types/Bool.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'date' => [int](../types/int.md), \];<a name="draftMessage"></a>  

***
<br><br>[$draftMessageEmpty](../constructors/draftMessageEmpty.md) = \[\];<a name="draftMessageEmpty"></a>  

***
<br><br>[$encryptedChat](../constructors/encryptedChat.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a_or_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \];<a name="encryptedChat"></a>  

***
<br><br>[$encryptedChatDiscarded](../constructors/encryptedChatDiscarded.md) = \['id' => [int](../types/int.md), \];<a name="encryptedChatDiscarded"></a>  

***
<br><br>[$encryptedChatEmpty](../constructors/encryptedChatEmpty.md) = \['id' => [int](../types/int.md), \];<a name="encryptedChatEmpty"></a>  

***
<br><br>[$encryptedChatRequested](../constructors/encryptedChatRequested.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a' => [bytes](../types/bytes.md), \];<a name="encryptedChatRequested"></a>  

***
<br><br>[$encryptedChatWaiting](../constructors/encryptedChatWaiting.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), \];<a name="encryptedChatWaiting"></a>  

***
<br><br>[$encryptedFile](../constructors/encryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'dc_id' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];<a name="encryptedFile"></a>  

***
<br><br>[$encryptedFileEmpty](../constructors/encryptedFileEmpty.md) = \[\];<a name="encryptedFileEmpty"></a>  

***
<br><br>[$encryptedMessage](../constructors/encryptedMessage.md) = \['chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'decrypted_message' => [DecryptedMessage](../types/DecryptedMessage.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];<a name="encryptedMessage"></a>  

***
<br><br>[$encryptedMessageService](../constructors/encryptedMessageService.md) = \['chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'decrypted_message' => [DecryptedMessage](../types/DecryptedMessage.md), \];<a name="encryptedMessageService"></a>  

***
<br><br>[$error](../constructors/error.md) = \['code' => [int](../types/int.md), 'text' => [string](../types/string.md), \];<a name="error"></a>  

***
<br><br>[$exportedMessageLink](../constructors/exportedMessageLink.md) = \['link' => [string](../types/string.md), \];<a name="exportedMessageLink"></a>  

***
<br><br>[$fileLocation](../constructors/fileLocation.md) = \['dc_id' => [int](../types/int.md), 'volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];<a name="fileLocation"></a>  

***
<br><br>[$fileLocationUnavailable](../constructors/fileLocationUnavailable.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];<a name="fileLocationUnavailable"></a>  

***
<br><br>[$foundGif](../constructors/foundGif.md) = \['url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="foundGif"></a>  

***
<br><br>[$foundGifCached](../constructors/foundGifCached.md) = \['url' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];<a name="foundGifCached"></a>  

***
<br><br>[$future\_salt](../constructors/future_salt.md) = \['valid_since' => [int](../types/int.md), 'valid_until' => [int](../types/int.md), 'salt' => [long](../types/long.md), \];<a name="future_salt"></a>  

[$future\_salts](../constructors/future_salts.md) = \['req_msg_id' => [long](../types/long.md), 'now' => [int](../types/int.md), 'salts' => \[[future\_salt](../constructors/future_salt.md)\], \];<a name="future_salts"></a>  

***
<br><br>[$game](../constructors/game.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'short_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];<a name="game"></a>  

***
<br><br>[$geoPoint](../constructors/geoPoint.md) = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \];<a name="geoPoint"></a>  

***
<br><br>[$geoPointEmpty](../constructors/geoPointEmpty.md) = \[\];<a name="geoPointEmpty"></a>  

***
<br><br>[$help\_appUpdate](../constructors/help_appUpdate.md) = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \];<a name="help_appUpdate"></a>  

[$help\_configSimple](../constructors/help_configSimple.md) = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'dc_id' => [int](../types/int.md), 'ip_port_list' => \[[ipPort](../constructors/ipPort.md)\], \];<a name="help_configSimple"></a>  

[$help\_inviteText](../constructors/help_inviteText.md) = \['message' => [string](../types/string.md), \];<a name="help_inviteText"></a>  

[$help\_noAppUpdate](../constructors/help_noAppUpdate.md) = \[\];<a name="help_noAppUpdate"></a>  

[$help\_support](../constructors/help_support.md) = \['phone_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \];<a name="help_support"></a>  

[$help\_termsOfService](../constructors/help_termsOfService.md) = \['text' => [string](../types/string.md), \];<a name="help_termsOfService"></a>  

***
<br><br>[$highScore](../constructors/highScore.md) = \['pos' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];<a name="highScore"></a>  

***
<br><br>[$http\_wait](../constructors/http_wait.md) = \['max_delay' => [int](../types/int.md), 'wait_after' => [int](../types/int.md), 'max_wait' => [int](../types/int.md), \];<a name="http_wait"></a>  

***
<br><br>[$importedContact](../constructors/importedContact.md) = \['user_id' => [int](../types/int.md), 'client_id' => [long](../types/long.md), \];<a name="importedContact"></a>  

***
<br><br>[$inlineBotSwitchPM](../constructors/inlineBotSwitchPM.md) = \['text' => [string](../types/string.md), 'start_param' => [string](../types/string.md), \];<a name="inlineBotSwitchPM"></a>  

***
<br><br>[$inputAppEvent](../constructors/inputAppEvent.md) = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \];<a name="inputAppEvent"></a>  

***
<br><br>[$inputBotInlineMessageGame](../constructors/inputBotInlineMessageGame.md) = \['reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageGame"></a>  

***
<br><br>[$inputBotInlineMessageID](../constructors/inputBotInlineMessageID.md) = \['dc_id' => [int](../types/int.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputBotInlineMessageID"></a>  

***
<br><br>[$inputBotInlineMessageMediaAuto](../constructors/inputBotInlineMessageMediaAuto.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageMediaAuto"></a>  

***
<br><br>[$inputBotInlineMessageMediaContact](../constructors/inputBotInlineMessageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageMediaContact"></a>  

***
<br><br>[$inputBotInlineMessageMediaGeo](../constructors/inputBotInlineMessageMediaGeo.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageMediaGeo"></a>  

***
<br><br>[$inputBotInlineMessageMediaVenue](../constructors/inputBotInlineMessageMediaVenue.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageMediaVenue"></a>  

***
<br><br>[$inputBotInlineMessageText](../constructors/inputBotInlineMessageText.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputBotInlineMessageText"></a>  

***
<br><br>[$inputBotInlineResult](../constructors/inputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];<a name="inputBotInlineResult"></a>  

***
<br><br>[$inputBotInlineResultDocument](../constructors/inputBotInlineResultDocument.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document' => [InputDocument](../types/InputDocument.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];<a name="inputBotInlineResultDocument"></a>  

***
<br><br>[$inputBotInlineResultGame](../constructors/inputBotInlineResultGame.md) = \['id' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];<a name="inputBotInlineResultGame"></a>  

***
<br><br>[$inputBotInlineResultPhoto](../constructors/inputBotInlineResultPhoto.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [InputPhoto](../types/InputPhoto.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];<a name="inputBotInlineResultPhoto"></a>  

***
<br><br>[$inputChannel](../constructors/inputChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputChannel"></a>  

***
<br><br>[$inputChannelEmpty](../constructors/inputChannelEmpty.md) = \[\];<a name="inputChannelEmpty"></a>  

***
<br><br>[$inputChatPhoto](../constructors/inputChatPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];<a name="inputChatPhoto"></a>  

***
<br><br>[$inputChatPhotoEmpty](../constructors/inputChatPhotoEmpty.md) = \[\];<a name="inputChatPhotoEmpty"></a>  

***
<br><br>[$inputChatUploadedPhoto](../constructors/inputChatUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), \];<a name="inputChatUploadedPhoto"></a>  

***
<br><br>[$inputDocument](../constructors/inputDocument.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputDocument"></a>  

***
<br><br>[$inputDocumentEmpty](../constructors/inputDocumentEmpty.md) = \[\];<a name="inputDocumentEmpty"></a>  

***
<br><br>[$inputDocumentFileLocation](../constructors/inputDocumentFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'version' => [int](../types/int.md), \];<a name="inputDocumentFileLocation"></a>  

***
<br><br>[$inputEncryptedChat](../constructors/inputEncryptedChat.md) = \['chat_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputEncryptedChat"></a>  

***
<br><br>[$inputEncryptedFile](../constructors/inputEncryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputEncryptedFile"></a>  

***
<br><br>[$inputEncryptedFileBigUploaded](../constructors/inputEncryptedFileBigUploaded.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];<a name="inputEncryptedFileBigUploaded"></a>  

***
<br><br>[$inputEncryptedFileEmpty](../constructors/inputEncryptedFileEmpty.md) = \[\];<a name="inputEncryptedFileEmpty"></a>  

***
<br><br>[$inputEncryptedFileLocation](../constructors/inputEncryptedFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputEncryptedFileLocation"></a>  

***
<br><br>[$inputEncryptedFileUploaded](../constructors/inputEncryptedFileUploaded.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'md5_checksum' => [string](../types/string.md), 'key_fingerprint' => [int](../types/int.md), \];<a name="inputEncryptedFileUploaded"></a>  

***
<br><br>[$inputFile](../constructors/inputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), 'md5_checksum' => [string](../types/string.md), \];<a name="inputFile"></a>  

***
<br><br>[$inputFileBig](../constructors/inputFileBig.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), \];<a name="inputFileBig"></a>  

***
<br><br>[$inputFileLocation](../constructors/inputFileLocation.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];<a name="inputFileLocation"></a>  

***
<br><br>[$inputGameID](../constructors/inputGameID.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputGameID"></a>  

***
<br><br>[$inputGameShortName](../constructors/inputGameShortName.md) = \['bot_id' => [InputUser](../types/InputUser.md), 'short_name' => [string](../types/string.md), \];<a name="inputGameShortName"></a>  

***
<br><br>[$inputGeoPoint](../constructors/inputGeoPoint.md) = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \];<a name="inputGeoPoint"></a>  

***
<br><br>[$inputGeoPointEmpty](../constructors/inputGeoPointEmpty.md) = \[\];<a name="inputGeoPointEmpty"></a>  

***
<br><br>[$inputMediaContact](../constructors/inputMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];<a name="inputMediaContact"></a>  

***
<br><br>[$inputMediaDocument](../constructors/inputMediaDocument.md) = \['id' => [InputDocument](../types/InputDocument.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaDocument"></a>  

***
<br><br>[$inputMediaDocumentExternal](../constructors/inputMediaDocumentExternal.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaDocumentExternal"></a>  

***
<br><br>[$inputMediaEmpty](../constructors/inputMediaEmpty.md) = \[\];<a name="inputMediaEmpty"></a>  

***
<br><br>[$inputMediaGame](../constructors/inputMediaGame.md) = \['id' => [InputGame](../types/InputGame.md), \];<a name="inputMediaGame"></a>  

***
<br><br>[$inputMediaGeoPoint](../constructors/inputMediaGeoPoint.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), \];<a name="inputMediaGeoPoint"></a>  

***
<br><br>[$inputMediaGifExternal](../constructors/inputMediaGifExternal.md) = \['url' => [string](../types/string.md), 'q' => [string](../types/string.md), \];<a name="inputMediaGifExternal"></a>  

***
<br><br>[$inputMediaInvoice](../constructors/inputMediaInvoice.md) = \['title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [InputWebDocument](../types/InputWebDocument.md), 'invoice' => [Invoice](../types/Invoice.md), 'payload' => [bytes](../types/bytes.md), 'provider' => [string](../types/string.md), 'start_param' => [string](../types/string.md), \];<a name="inputMediaInvoice"></a>  

***
<br><br>[$inputMediaPhoto](../constructors/inputMediaPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaPhoto"></a>  

***
<br><br>[$inputMediaPhotoExternal](../constructors/inputMediaPhotoExternal.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaPhotoExternal"></a>  

***
<br><br>[$inputMediaUploadedDocument](../constructors/inputMediaUploadedDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];<a name="inputMediaUploadedDocument"></a>  

***
<br><br>[$inputMediaUploadedPhoto](../constructors/inputMediaUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];<a name="inputMediaUploadedPhoto"></a>  

***
<br><br>[$inputMediaUploadedThumbDocument](../constructors/inputMediaUploadedThumbDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];<a name="inputMediaUploadedThumbDocument"></a>  

***
<br><br>[$inputMediaVenue](../constructors/inputMediaVenue.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];<a name="inputMediaVenue"></a>  

***
<br><br>[$inputMessageEntityMentionName](../constructors/inputMessageEntityMentionName.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \];<a name="inputMessageEntityMentionName"></a>  

***
<br><br>[$inputMessagesFilterChatPhotos](../constructors/inputMessagesFilterChatPhotos.md) = \[\];<a name="inputMessagesFilterChatPhotos"></a>  

***
<br><br>[$inputMessagesFilterDocument](../constructors/inputMessagesFilterDocument.md) = \[\];<a name="inputMessagesFilterDocument"></a>  

***
<br><br>[$inputMessagesFilterEmpty](../constructors/inputMessagesFilterEmpty.md) = \[\];<a name="inputMessagesFilterEmpty"></a>  

***
<br><br>[$inputMessagesFilterGif](../constructors/inputMessagesFilterGif.md) = \[\];<a name="inputMessagesFilterGif"></a>  

***
<br><br>[$inputMessagesFilterMusic](../constructors/inputMessagesFilterMusic.md) = \[\];<a name="inputMessagesFilterMusic"></a>  

***
<br><br>[$inputMessagesFilterPhoneCalls](../constructors/inputMessagesFilterPhoneCalls.md) = \['missed' => [Bool](../types/Bool.md), \];<a name="inputMessagesFilterPhoneCalls"></a>  

***
<br><br>[$inputMessagesFilterPhotoVideo](../constructors/inputMessagesFilterPhotoVideo.md) = \[\];<a name="inputMessagesFilterPhotoVideo"></a>  

***
<br><br>[$inputMessagesFilterPhotoVideoDocuments](../constructors/inputMessagesFilterPhotoVideoDocuments.md) = \[\];<a name="inputMessagesFilterPhotoVideoDocuments"></a>  

***
<br><br>[$inputMessagesFilterPhotos](../constructors/inputMessagesFilterPhotos.md) = \[\];<a name="inputMessagesFilterPhotos"></a>  

***
<br><br>[$inputMessagesFilterRoundVideo](../constructors/inputMessagesFilterRoundVideo.md) = \[\];<a name="inputMessagesFilterRoundVideo"></a>  

***
<br><br>[$inputMessagesFilterRoundVoice](../constructors/inputMessagesFilterRoundVoice.md) = \[\];<a name="inputMessagesFilterRoundVoice"></a>  

***
<br><br>[$inputMessagesFilterUrl](../constructors/inputMessagesFilterUrl.md) = \[\];<a name="inputMessagesFilterUrl"></a>  

***
<br><br>[$inputMessagesFilterVideo](../constructors/inputMessagesFilterVideo.md) = \[\];<a name="inputMessagesFilterVideo"></a>  

***
<br><br>[$inputMessagesFilterVoice](../constructors/inputMessagesFilterVoice.md) = \[\];<a name="inputMessagesFilterVoice"></a>  

***
<br><br>[$inputNotifyAll](../constructors/inputNotifyAll.md) = \[\];<a name="inputNotifyAll"></a>  

***
<br><br>[$inputNotifyChats](../constructors/inputNotifyChats.md) = \[\];<a name="inputNotifyChats"></a>  

***
<br><br>[$inputNotifyPeer](../constructors/inputNotifyPeer.md) = \['peer' => [InputPeer](../types/InputPeer.md), \];<a name="inputNotifyPeer"></a>  

***
<br><br>[$inputNotifyUsers](../constructors/inputNotifyUsers.md) = \[\];<a name="inputNotifyUsers"></a>  

***
<br><br>[$inputPaymentCredentials](../constructors/inputPaymentCredentials.md) = \['save' => [Bool](../types/Bool.md), 'data' => [DataJSON](../types/DataJSON.md), \];<a name="inputPaymentCredentials"></a>  

***
<br><br>[$inputPaymentCredentialsSaved](../constructors/inputPaymentCredentialsSaved.md) = \['id' => [string](../types/string.md), 'tmp_password' => [bytes](../types/bytes.md), \];<a name="inputPaymentCredentialsSaved"></a>  

***
<br><br>[$inputPeerChannel](../constructors/inputPeerChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPeerChannel"></a>  

***
<br><br>[$inputPeerChat](../constructors/inputPeerChat.md) = \['chat_id' => [int](../types/int.md), \];<a name="inputPeerChat"></a>  

***
<br><br>[$inputPeerEmpty](../constructors/inputPeerEmpty.md) = \[\];<a name="inputPeerEmpty"></a>  

***
<br><br>[$inputPeerNotifyEventsAll](../constructors/inputPeerNotifyEventsAll.md) = \[\];<a name="inputPeerNotifyEventsAll"></a>  

***
<br><br>[$inputPeerNotifyEventsEmpty](../constructors/inputPeerNotifyEventsEmpty.md) = \[\];<a name="inputPeerNotifyEventsEmpty"></a>  

***
<br><br>[$inputPeerNotifySettings](../constructors/inputPeerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];<a name="inputPeerNotifySettings"></a>  

***
<br><br>[$inputPeerSelf](../constructors/inputPeerSelf.md) = \[\];<a name="inputPeerSelf"></a>  

***
<br><br>[$inputPeerUser](../constructors/inputPeerUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPeerUser"></a>  

***
<br><br>[$inputPhoneCall](../constructors/inputPhoneCall.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPhoneCall"></a>  

***
<br><br>[$inputPhoneContact](../constructors/inputPhoneContact.md) = \['client_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];<a name="inputPhoneContact"></a>  

***
<br><br>[$inputPhoto](../constructors/inputPhoto.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPhoto"></a>  

***
<br><br>[$inputPhotoEmpty](../constructors/inputPhotoEmpty.md) = \[\];<a name="inputPhotoEmpty"></a>  

***
<br><br>[$inputPrivacyKeyChatInvite](../constructors/inputPrivacyKeyChatInvite.md) = \[\];<a name="inputPrivacyKeyChatInvite"></a>  

***
<br><br>[$inputPrivacyKeyPhoneCall](../constructors/inputPrivacyKeyPhoneCall.md) = \[\];<a name="inputPrivacyKeyPhoneCall"></a>  

***
<br><br>[$inputPrivacyKeyStatusTimestamp](../constructors/inputPrivacyKeyStatusTimestamp.md) = \[\];<a name="inputPrivacyKeyStatusTimestamp"></a>  

***
<br><br>[$inputPrivacyValueAllowAll](../constructors/inputPrivacyValueAllowAll.md) = \[\];<a name="inputPrivacyValueAllowAll"></a>  

***
<br><br>[$inputPrivacyValueAllowContacts](../constructors/inputPrivacyValueAllowContacts.md) = \[\];<a name="inputPrivacyValueAllowContacts"></a>  

***
<br><br>[$inputPrivacyValueAllowUsers](../constructors/inputPrivacyValueAllowUsers.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];<a name="inputPrivacyValueAllowUsers"></a>  

***
<br><br>[$inputPrivacyValueDisallowAll](../constructors/inputPrivacyValueDisallowAll.md) = \[\];<a name="inputPrivacyValueDisallowAll"></a>  

***
<br><br>[$inputPrivacyValueDisallowContacts](../constructors/inputPrivacyValueDisallowContacts.md) = \[\];<a name="inputPrivacyValueDisallowContacts"></a>  

***
<br><br>[$inputPrivacyValueDisallowUsers](../constructors/inputPrivacyValueDisallowUsers.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];<a name="inputPrivacyValueDisallowUsers"></a>  

***
<br><br>[$inputReportReasonOther](../constructors/inputReportReasonOther.md) = \['text' => [string](../types/string.md), \];<a name="inputReportReasonOther"></a>  

***
<br><br>[$inputReportReasonPornography](../constructors/inputReportReasonPornography.md) = \[\];<a name="inputReportReasonPornography"></a>  

***
<br><br>[$inputReportReasonSpam](../constructors/inputReportReasonSpam.md) = \[\];<a name="inputReportReasonSpam"></a>  

***
<br><br>[$inputReportReasonViolence](../constructors/inputReportReasonViolence.md) = \[\];<a name="inputReportReasonViolence"></a>  

***
<br><br>[$inputStickerSetEmpty](../constructors/inputStickerSetEmpty.md) = \[\];<a name="inputStickerSetEmpty"></a>  

***
<br><br>[$inputStickerSetID](../constructors/inputStickerSetID.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputStickerSetID"></a>  

***
<br><br>[$inputStickerSetItem](../constructors/inputStickerSetItem.md) = \['document' => [InputDocument](../types/InputDocument.md), 'emoji' => [string](../types/string.md), 'mask_coords' => [MaskCoords](../types/MaskCoords.md), \];<a name="inputStickerSetItem"></a>  

***
<br><br>[$inputStickerSetShortName](../constructors/inputStickerSetShortName.md) = \['short_name' => [string](../types/string.md), \];<a name="inputStickerSetShortName"></a>  

***
<br><br>[$inputStickeredMediaDocument](../constructors/inputStickeredMediaDocument.md) = \['id' => [InputDocument](../types/InputDocument.md), \];<a name="inputStickeredMediaDocument"></a>  

***
<br><br>[$inputStickeredMediaPhoto](../constructors/inputStickeredMediaPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];<a name="inputStickeredMediaPhoto"></a>  

***
<br><br>[$inputUser](../constructors/inputUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputUser"></a>  

***
<br><br>[$inputUserEmpty](../constructors/inputUserEmpty.md) = \[\];<a name="inputUserEmpty"></a>  

***
<br><br>[$inputUserSelf](../constructors/inputUserSelf.md) = \[\];<a name="inputUserSelf"></a>  

***
<br><br>[$inputWebDocument](../constructors/inputWebDocument.md) = \['url' => [string](../types/string.md), 'size' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];<a name="inputWebDocument"></a>  

***
<br><br>[$inputWebFileLocation](../constructors/inputWebFileLocation.md) = \['url' => [string](../types/string.md), 'access_hash' => [long](../types/long.md), \];<a name="inputWebFileLocation"></a>  

***
<br><br>[$invoice](../constructors/invoice.md) = \['test' => [Bool](../types/Bool.md), 'name_requested' => [Bool](../types/Bool.md), 'phone_requested' => [Bool](../types/Bool.md), 'email_requested' => [Bool](../types/Bool.md), 'shipping_address_requested' => [Bool](../types/Bool.md), 'flexible' => [Bool](../types/Bool.md), 'currency' => [string](../types/string.md), 'prices' => \[[LabeledPrice](../types/LabeledPrice.md)\], \];<a name="invoice"></a>  

***
<br><br>[$ipPort](../constructors/ipPort.md) = \['ipv4' => [int](../types/int.md), 'port' => [int](../types/int.md), \];<a name="ipPort"></a>  

***
<br><br>[$keyboardButton](../constructors/keyboardButton.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButton"></a>  

***
<br><br>[$keyboardButtonBuy](../constructors/keyboardButtonBuy.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButtonBuy"></a>  

***
<br><br>[$keyboardButtonCallback](../constructors/keyboardButtonCallback.md) = \['text' => [string](../types/string.md), 'data' => [bytes](../types/bytes.md), \];<a name="keyboardButtonCallback"></a>  

***
<br><br>[$keyboardButtonGame](../constructors/keyboardButtonGame.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButtonGame"></a>  

***
<br><br>[$keyboardButtonRequestGeoLocation](../constructors/keyboardButtonRequestGeoLocation.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButtonRequestGeoLocation"></a>  

***
<br><br>[$keyboardButtonRequestPhone](../constructors/keyboardButtonRequestPhone.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButtonRequestPhone"></a>  

***
<br><br>[$keyboardButtonRow](../constructors/keyboardButtonRow.md) = \['buttons' => \[[KeyboardButton](../types/KeyboardButton.md)\], \];<a name="keyboardButtonRow"></a>  

***
<br><br>[$keyboardButtonSwitchInline](../constructors/keyboardButtonSwitchInline.md) = \['same_peer' => [Bool](../types/Bool.md), 'text' => [string](../types/string.md), 'query' => [string](../types/string.md), \];<a name="keyboardButtonSwitchInline"></a>  

***
<br><br>[$keyboardButtonUrl](../constructors/keyboardButtonUrl.md) = \['text' => [string](../types/string.md), 'url' => [string](../types/string.md), \];<a name="keyboardButtonUrl"></a>  

***
<br><br>[$labeledPrice](../constructors/labeledPrice.md) = \['label' => [string](../types/string.md), 'amount' => [long](../types/long.md), \];<a name="labeledPrice"></a>  

***
<br><br>[$langPackDifference](../constructors/langPackDifference.md) = \['lang_code' => [string](../types/string.md), 'from_version' => [int](../types/int.md), 'version' => [int](../types/int.md), 'strings' => \[[LangPackString](../types/LangPackString.md)\], \];<a name="langPackDifference"></a>  

***
<br><br>[$langPackLanguage](../constructors/langPackLanguage.md) = \['name' => [string](../types/string.md), 'native_name' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \];<a name="langPackLanguage"></a>  

***
<br><br>[$langPackString](../constructors/langPackString.md) = \['key' => [string](../types/string.md), 'value' => [string](../types/string.md), \];<a name="langPackString"></a>  

***
<br><br>[$langPackStringDeleted](../constructors/langPackStringDeleted.md) = \['key' => [string](../types/string.md), \];<a name="langPackStringDeleted"></a>  

***
<br><br>[$langPackStringPluralized](../constructors/langPackStringPluralized.md) = \['key' => [string](../types/string.md), 'zero_value' => [string](../types/string.md), 'one_value' => [string](../types/string.md), 'two_value' => [string](../types/string.md), 'few_value' => [string](../types/string.md), 'many_value' => [string](../types/string.md), 'other_value' => [string](../types/string.md), \];<a name="langPackStringPluralized"></a>  

***
<br><br>[$maskCoords](../constructors/maskCoords.md) = \['n' => [int](../types/int.md), 'x' => [double](../types/double.md), 'y' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \];<a name="maskCoords"></a>  

***
<br><br>[$message](../constructors/message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'views' => [int](../types/int.md), 'edit_date' => [int](../types/int.md), \];<a name="message"></a>  

***
<br><br>[$messageActionChannelCreate](../constructors/messageActionChannelCreate.md) = \['title' => [string](../types/string.md), \];<a name="messageActionChannelCreate"></a>  

***
<br><br>[$messageActionChannelMigrateFrom](../constructors/messageActionChannelMigrateFrom.md) = \['title' => [string](../types/string.md), 'chat_id' => [int](../types/int.md), \];<a name="messageActionChannelMigrateFrom"></a>  

***
<br><br>[$messageActionChatAddUser](../constructors/messageActionChatAddUser.md) = \['users' => \[[int](../types/int.md)\], \];<a name="messageActionChatAddUser"></a>  

***
<br><br>[$messageActionChatCreate](../constructors/messageActionChatCreate.md) = \['title' => [string](../types/string.md), 'users' => \[[int](../types/int.md)\], \];<a name="messageActionChatCreate"></a>  

***
<br><br>[$messageActionChatDeletePhoto](../constructors/messageActionChatDeletePhoto.md) = \[\];<a name="messageActionChatDeletePhoto"></a>  

***
<br><br>[$messageActionChatDeleteUser](../constructors/messageActionChatDeleteUser.md) = \['user_id' => [int](../types/int.md), \];<a name="messageActionChatDeleteUser"></a>  

***
<br><br>[$messageActionChatEditPhoto](../constructors/messageActionChatEditPhoto.md) = \['photo' => [Photo](../types/Photo.md), \];<a name="messageActionChatEditPhoto"></a>  

***
<br><br>[$messageActionChatEditTitle](../constructors/messageActionChatEditTitle.md) = \['title' => [string](../types/string.md), \];<a name="messageActionChatEditTitle"></a>  

***
<br><br>[$messageActionChatJoinedByLink](../constructors/messageActionChatJoinedByLink.md) = \['inviter_id' => [int](../types/int.md), \];<a name="messageActionChatJoinedByLink"></a>  

***
<br><br>[$messageActionChatMigrateTo](../constructors/messageActionChatMigrateTo.md) = \['channel_id' => [int](../types/int.md), \];<a name="messageActionChatMigrateTo"></a>  

***
<br><br>[$messageActionEmpty](../constructors/messageActionEmpty.md) = \[\];<a name="messageActionEmpty"></a>  

***
<br><br>[$messageActionGameScore](../constructors/messageActionGameScore.md) = \['game_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];<a name="messageActionGameScore"></a>  

***
<br><br>[$messageActionHistoryClear](../constructors/messageActionHistoryClear.md) = \[\];<a name="messageActionHistoryClear"></a>  

***
<br><br>[$messageActionPaymentSent](../constructors/messageActionPaymentSent.md) = \['currency' => [string](../types/string.md), 'total_amount' => [long](../types/long.md), \];<a name="messageActionPaymentSent"></a>  

***
<br><br>[$messageActionPaymentSentMe](../constructors/messageActionPaymentSentMe.md) = \['currency' => [string](../types/string.md), 'total_amount' => [long](../types/long.md), 'payload' => [bytes](../types/bytes.md), 'info' => [PaymentRequestedInfo](../types/PaymentRequestedInfo.md), 'shipping_option_id' => [string](../types/string.md), 'charge' => [PaymentCharge](../types/PaymentCharge.md), \];<a name="messageActionPaymentSentMe"></a>  

***
<br><br>[$messageActionPhoneCall](../constructors/messageActionPhoneCall.md) = \['call_id' => [long](../types/long.md), 'reason' => [PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md), 'duration' => [int](../types/int.md), \];<a name="messageActionPhoneCall"></a>  

***
<br><br>[$messageActionPinMessage](../constructors/messageActionPinMessage.md) = \[\];<a name="messageActionPinMessage"></a>  

***
<br><br>[$messageEmpty](../constructors/messageEmpty.md) = \['id' => [int](../types/int.md), \];<a name="messageEmpty"></a>  

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
<br><br>[$messageEntityPre](../constructors/messageEntityPre.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];<a name="messageEntityPre"></a>  

***
<br><br>[$messageEntityTextUrl](../constructors/messageEntityTextUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];<a name="messageEntityTextUrl"></a>  

***
<br><br>[$messageEntityUnknown](../constructors/messageEntityUnknown.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityUnknown"></a>  

***
<br><br>[$messageEntityUrl](../constructors/messageEntityUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityUrl"></a>  

***
<br><br>[$messageFwdHeader](../constructors/messageFwdHeader.md) = \['from_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'channel_id' => [int](../types/int.md), 'channel_post' => [int](../types/int.md), \];<a name="messageFwdHeader"></a>  

***
<br><br>[$messageMediaContact](../constructors/messageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];<a name="messageMediaContact"></a>  

***
<br><br>[$messageMediaDocument](../constructors/messageMediaDocument.md) = \['document' => [Document](../types/Document.md), 'caption' => [string](../types/string.md), \];<a name="messageMediaDocument"></a>  

***
<br><br>[$messageMediaEmpty](../constructors/messageMediaEmpty.md) = \[\];<a name="messageMediaEmpty"></a>  

***
<br><br>[$messageMediaGame](../constructors/messageMediaGame.md) = \['game' => [Game](../types/Game.md), \];<a name="messageMediaGame"></a>  

***
<br><br>[$messageMediaGeo](../constructors/messageMediaGeo.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), \];<a name="messageMediaGeo"></a>  

***
<br><br>[$messageMediaInvoice](../constructors/messageMediaInvoice.md) = \['shipping_address_requested' => [Bool](../types/Bool.md), 'test' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [WebDocument](../types/WebDocument.md), 'receipt_msg_id' => [int](../types/int.md), 'currency' => [string](../types/string.md), 'total_amount' => [long](../types/long.md), 'start_param' => [string](../types/string.md), \];<a name="messageMediaInvoice"></a>  

***
<br><br>[$messageMediaPhoto](../constructors/messageMediaPhoto.md) = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \];<a name="messageMediaPhoto"></a>  

***
<br><br>[$messageMediaUnsupported](../constructors/messageMediaUnsupported.md) = \[\];<a name="messageMediaUnsupported"></a>  

***
<br><br>[$messageMediaVenue](../constructors/messageMediaVenue.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];<a name="messageMediaVenue"></a>  

***
<br><br>[$messageMediaWebPage](../constructors/messageMediaWebPage.md) = \['webpage' => [WebPage](../types/WebPage.md), \];<a name="messageMediaWebPage"></a>  

***
<br><br>[$messageRange](../constructors/messageRange.md) = \['min_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];<a name="messageRange"></a>  

***
<br><br>[$messageService](../constructors/messageService.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \];<a name="messageService"></a>  

***
<br><br>[$messages\_affectedHistory](../constructors/messages_affectedHistory.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \];<a name="messages_affectedHistory"></a>  

[$messages\_affectedMessages](../constructors/messages_affectedMessages.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="messages_affectedMessages"></a>  

[$messages\_allStickers](../constructors/messages_allStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSet](../types/StickerSet.md)\], \];<a name="messages_allStickers"></a>  

[$messages\_allStickersNotModified](../constructors/messages_allStickersNotModified.md) = \[\];<a name="messages_allStickersNotModified"></a>  

[$messages\_archivedStickers](../constructors/messages_archivedStickers.md) = \['count' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];<a name="messages_archivedStickers"></a>  

[$messages\_botCallbackAnswer](../constructors/messages_botCallbackAnswer.md) = \['alert' => [Bool](../types/Bool.md), 'has_url' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), 'cache_time' => [int](../types/int.md), \];<a name="messages_botCallbackAnswer"></a>  

[$messages\_botResults](../constructors/messages_botResults.md) = \['gallery' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'next_offset' => [string](../types/string.md), 'switch_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), 'results' => \[[BotInlineResult](../types/BotInlineResult.md)\], 'cache_time' => [int](../types/int.md), \];<a name="messages_botResults"></a>  

[$messages\_channelMessages](../constructors/messages_channelMessages.md) = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_channelMessages"></a>  

[$messages\_chatFull](../constructors/messages_chatFull.md) = \['full_chat' => [ChatFull](../types/ChatFull.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_chatFull"></a>  

[$messages\_chats](../constructors/messages_chats.md) = \['chats' => \[[Chat](../types/Chat.md)\], \];<a name="messages_chats"></a>  

[$messages\_chatsSlice](../constructors/messages_chatsSlice.md) = \['count' => [int](../types/int.md), 'chats' => \[[Chat](../types/Chat.md)\], \];<a name="messages_chatsSlice"></a>  

[$messages\_dhConfig](../constructors/messages_dhConfig.md) = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \];<a name="messages_dhConfig"></a>  

[$messages\_dhConfigNotModified](../constructors/messages_dhConfigNotModified.md) = \['random' => [bytes](../types/bytes.md), \];<a name="messages_dhConfigNotModified"></a>  

[$messages\_dialogs](../constructors/messages_dialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_dialogs"></a>  

[$messages\_dialogsSlice](../constructors/messages_dialogsSlice.md) = \['count' => [int](../types/int.md), 'dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_dialogsSlice"></a>  

[$messages\_featuredStickers](../constructors/messages_featuredStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], 'unread' => \[[long](../types/long.md)\], \];<a name="messages_featuredStickers"></a>  

[$messages\_featuredStickersNotModified](../constructors/messages_featuredStickersNotModified.md) = \[\];<a name="messages_featuredStickersNotModified"></a>  

[$messages\_foundGifs](../constructors/messages_foundGifs.md) = \['next_offset' => [int](../types/int.md), 'results' => \[[FoundGif](../types/FoundGif.md)\], \];<a name="messages_foundGifs"></a>  

[$messages\_highScores](../constructors/messages_highScores.md) = \['scores' => \[[HighScore](../types/HighScore.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_highScores"></a>  

[$messages\_messageEditData](../constructors/messages_messageEditData.md) = \['caption' => [Bool](../types/Bool.md), \];<a name="messages_messageEditData"></a>  

[$messages\_messages](../constructors/messages_messages.md) = \['messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_messages"></a>  

[$messages\_messagesSlice](../constructors/messages_messagesSlice.md) = \['count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_messagesSlice"></a>  

[$messages\_peerDialogs](../constructors/messages_peerDialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];<a name="messages_peerDialogs"></a>  

[$messages\_recentStickers](../constructors/messages_recentStickers.md) = \['hash' => [int](../types/int.md), 'stickers' => \[[Document](../types/Document.md)\], \];<a name="messages_recentStickers"></a>  

[$messages\_recentStickersNotModified](../constructors/messages_recentStickersNotModified.md) = \[\];<a name="messages_recentStickersNotModified"></a>  

[$messages\_savedGifs](../constructors/messages_savedGifs.md) = \['hash' => [int](../types/int.md), 'gifs' => \[[Document](../types/Document.md)\], \];<a name="messages_savedGifs"></a>  

[$messages\_savedGifsNotModified](../constructors/messages_savedGifsNotModified.md) = \[\];<a name="messages_savedGifsNotModified"></a>  

[$messages\_sentEncryptedFile](../constructors/messages_sentEncryptedFile.md) = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];<a name="messages_sentEncryptedFile"></a>  

[$messages\_sentEncryptedMessage](../constructors/messages_sentEncryptedMessage.md) = \['date' => [int](../types/int.md), \];<a name="messages_sentEncryptedMessage"></a>  

[$messages\_stickerSet](../constructors/messages_stickerSet.md) = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => \[[StickerPack](../types/StickerPack.md)\], 'documents' => \[[Document](../types/Document.md)\], \];<a name="messages_stickerSet"></a>  

[$messages\_stickerSetInstallResultArchive](../constructors/messages_stickerSetInstallResultArchive.md) = \['sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];<a name="messages_stickerSetInstallResultArchive"></a>  

[$messages\_stickerSetInstallResultSuccess](../constructors/messages_stickerSetInstallResultSuccess.md) = \[\];<a name="messages_stickerSetInstallResultSuccess"></a>  

[$messages\_stickers](../constructors/messages_stickers.md) = \['hash' => [string](../types/string.md), 'stickers' => \[[Document](../types/Document.md)\], \];<a name="messages_stickers"></a>  

[$messages\_stickersNotModified](../constructors/messages_stickersNotModified.md) = \[\];<a name="messages_stickersNotModified"></a>  

***
<br><br>[$msg\_detailed\_info](../constructors/msg_detailed_info.md) = \['msg_id' => [long](../types/long.md), 'answer_msg_id' => [long](../types/long.md), 'bytes' => [int](../types/int.md), 'status' => [int](../types/int.md), \];<a name="msg_detailed_info"></a>  

[$msg\_new\_detailed\_info](../constructors/msg_new_detailed_info.md) = \['answer_msg_id' => [long](../types/long.md), 'bytes' => [int](../types/int.md), 'status' => [int](../types/int.md), \];<a name="msg_new_detailed_info"></a>  

[$msg\_resend\_req](../constructors/msg_resend_req.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msg_resend_req"></a>  

***
<br><br>[$msgs\_ack](../constructors/msgs_ack.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msgs_ack"></a>  

[$msgs\_all\_info](../constructors/msgs_all_info.md) = \['msg_ids' => \[[long](../types/long.md)\], 'info' => [string](../types/string.md), \];<a name="msgs_all_info"></a>  

[$msgs\_state\_info](../constructors/msgs_state_info.md) = \['req_msg_id' => [long](../types/long.md), 'info' => [string](../types/string.md), \];<a name="msgs_state_info"></a>  

[$msgs\_state\_req](../constructors/msgs_state_req.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msgs_state_req"></a>  

***
<br><br>[$nearestDc](../constructors/nearestDc.md) = \['country' => [string](../types/string.md), 'this_dc' => [int](../types/int.md), 'nearest_dc' => [int](../types/int.md), \];<a name="nearestDc"></a>  

***
<br><br>[$new\_session\_created](../constructors/new_session_created.md) = \['first_msg_id' => [long](../types/long.md), 'unique_id' => [long](../types/long.md), 'server_salt' => [long](../types/long.md), \];<a name="new_session_created"></a>  

***
<br><br>[$notifyAll](../constructors/notifyAll.md) = \[\];<a name="notifyAll"></a>  

***
<br><br>[$notifyChats](../constructors/notifyChats.md) = \[\];<a name="notifyChats"></a>  

***
<br><br>[$notifyPeer](../constructors/notifyPeer.md) = \['peer' => [Peer](../types/Peer.md), \];<a name="notifyPeer"></a>  

***
<br><br>[$notifyUsers](../constructors/notifyUsers.md) = \[\];<a name="notifyUsers"></a>  

***
<br><br>[$null](../constructors/null.md) = \[\];<a name="null"></a>  

***
<br><br>[$p\_q\_inner\_data](../constructors/p_q_inner_data.md) = \['pq' => [string](../types/string.md), 'p' => [string](../types/string.md), 'q' => [string](../types/string.md), 'nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce' => [int256](../types/int256.md), \];<a name="p_q_inner_data"></a>  

***
<br><br>[$pageBlockAnchor](../constructors/pageBlockAnchor.md) = \['name' => [string](../types/string.md), \];<a name="pageBlockAnchor"></a>  

***
<br><br>[$pageBlockAudio](../constructors/pageBlockAudio.md) = \['audio_id' => [long](../types/long.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockAudio"></a>  

***
<br><br>[$pageBlockAuthorDate](../constructors/pageBlockAuthorDate.md) = \['author' => [RichText](../types/RichText.md), 'published_date' => [int](../types/int.md), \];<a name="pageBlockAuthorDate"></a>  

***
<br><br>[$pageBlockBlockquote](../constructors/pageBlockBlockquote.md) = \['text' => [RichText](../types/RichText.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockBlockquote"></a>  

***
<br><br>[$pageBlockChannel](../constructors/pageBlockChannel.md) = \['channel' => [Chat](../types/Chat.md), \];<a name="pageBlockChannel"></a>  

***
<br><br>[$pageBlockCollage](../constructors/pageBlockCollage.md) = \['items' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockCollage"></a>  

***
<br><br>[$pageBlockCover](../constructors/pageBlockCover.md) = \['cover' => [PageBlock](../types/PageBlock.md), \];<a name="pageBlockCover"></a>  

***
<br><br>[$pageBlockDivider](../constructors/pageBlockDivider.md) = \[\];<a name="pageBlockDivider"></a>  

***
<br><br>[$pageBlockEmbed](../constructors/pageBlockEmbed.md) = \['full_width' => [Bool](../types/Bool.md), 'allow_scrolling' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'html' => [string](../types/string.md), 'poster_photo_id' => [long](../types/long.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockEmbed"></a>  

***
<br><br>[$pageBlockEmbedPost](../constructors/pageBlockEmbedPost.md) = \['url' => [string](../types/string.md), 'webpage_id' => [long](../types/long.md), 'author_photo_id' => [long](../types/long.md), 'author' => [string](../types/string.md), 'date' => [int](../types/int.md), 'blocks' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockEmbedPost"></a>  

***
<br><br>[$pageBlockFooter](../constructors/pageBlockFooter.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockFooter"></a>  

***
<br><br>[$pageBlockHeader](../constructors/pageBlockHeader.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockHeader"></a>  

***
<br><br>[$pageBlockList](../constructors/pageBlockList.md) = \['ordered' => [Bool](../types/Bool.md), 'items' => \[[RichText](../types/RichText.md)\], \];<a name="pageBlockList"></a>  

***
<br><br>[$pageBlockParagraph](../constructors/pageBlockParagraph.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockParagraph"></a>  

***
<br><br>[$pageBlockPhoto](../constructors/pageBlockPhoto.md) = \['photo_id' => [long](../types/long.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockPhoto"></a>  

***
<br><br>[$pageBlockPreformatted](../constructors/pageBlockPreformatted.md) = \['text' => [RichText](../types/RichText.md), 'language' => [string](../types/string.md), \];<a name="pageBlockPreformatted"></a>  

***
<br><br>[$pageBlockPullquote](../constructors/pageBlockPullquote.md) = \['text' => [RichText](../types/RichText.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockPullquote"></a>  

***
<br><br>[$pageBlockSlideshow](../constructors/pageBlockSlideshow.md) = \['items' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockSlideshow"></a>  

***
<br><br>[$pageBlockSubheader](../constructors/pageBlockSubheader.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockSubheader"></a>  

***
<br><br>[$pageBlockSubtitle](../constructors/pageBlockSubtitle.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockSubtitle"></a>  

***
<br><br>[$pageBlockTitle](../constructors/pageBlockTitle.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockTitle"></a>  

***
<br><br>[$pageBlockUnsupported](../constructors/pageBlockUnsupported.md) = \[\];<a name="pageBlockUnsupported"></a>  

***
<br><br>[$pageBlockVideo](../constructors/pageBlockVideo.md) = \['autoplay' => [Bool](../types/Bool.md), 'loop' => [Bool](../types/Bool.md), 'video_id' => [long](../types/long.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockVideo"></a>  

***
<br><br>[$pageFull](../constructors/pageFull.md) = \['blocks' => \[[PageBlock](../types/PageBlock.md)\], 'photos' => \[[Photo](../types/Photo.md)\], 'documents' => \[[Document](../types/Document.md)\], \];<a name="pageFull"></a>  

***
<br><br>[$pagePart](../constructors/pagePart.md) = \['blocks' => \[[PageBlock](../types/PageBlock.md)\], 'photos' => \[[Photo](../types/Photo.md)\], 'documents' => \[[Document](../types/Document.md)\], \];<a name="pagePart"></a>  

***
<br><br>[$paymentCharge](../constructors/paymentCharge.md) = \['id' => [string](../types/string.md), 'provider_charge_id' => [string](../types/string.md), \];<a name="paymentCharge"></a>  

***
<br><br>[$paymentRequestedInfo](../constructors/paymentRequestedInfo.md) = \['name' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'email' => [string](../types/string.md), 'shipping_address' => [PostAddress](../types/PostAddress.md), \];<a name="paymentRequestedInfo"></a>  

***
<br><br>[$paymentSavedCredentialsCard](../constructors/paymentSavedCredentialsCard.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), \];<a name="paymentSavedCredentialsCard"></a>  

***
<br><br>[$payments\_paymentForm](../constructors/payments_paymentForm.md) = \['can_save_credentials' => [Bool](../types/Bool.md), 'password_missing' => [Bool](../types/Bool.md), 'bot_id' => [int](../types/int.md), 'invoice' => [Invoice](../types/Invoice.md), 'provider_id' => [int](../types/int.md), 'url' => [string](../types/string.md), 'native_provider' => [string](../types/string.md), 'native_params' => [DataJSON](../types/DataJSON.md), 'saved_info' => [PaymentRequestedInfo](../types/PaymentRequestedInfo.md), 'saved_credentials' => [PaymentSavedCredentials](../types/PaymentSavedCredentials.md), 'users' => \[[User](../types/User.md)\], \];<a name="payments_paymentForm"></a>  

[$payments\_paymentReceipt](../constructors/payments_paymentReceipt.md) = \['date' => [int](../types/int.md), 'bot_id' => [int](../types/int.md), 'invoice' => [Invoice](../types/Invoice.md), 'provider_id' => [int](../types/int.md), 'info' => [PaymentRequestedInfo](../types/PaymentRequestedInfo.md), 'shipping' => [ShippingOption](../types/ShippingOption.md), 'currency' => [string](../types/string.md), 'total_amount' => [long](../types/long.md), 'credentials_title' => [string](../types/string.md), 'users' => \[[User](../types/User.md)\], \];<a name="payments_paymentReceipt"></a>  

[$payments\_paymentResult](../constructors/payments_paymentResult.md) = \['updates' => [Updates](../types/Updates.md), \];<a name="payments_paymentResult"></a>  

[$payments\_paymentVerficationNeeded](../constructors/payments_paymentVerficationNeeded.md) = \['url' => [string](../types/string.md), \];<a name="payments_paymentVerficationNeeded"></a>  

[$payments\_savedInfo](../constructors/payments_savedInfo.md) = \['has_saved_credentials' => [Bool](../types/Bool.md), 'saved_info' => [PaymentRequestedInfo](../types/PaymentRequestedInfo.md), \];<a name="payments_savedInfo"></a>  

[$payments\_validatedRequestedInfo](../constructors/payments_validatedRequestedInfo.md) = \['id' => [string](../types/string.md), 'shipping_options' => \[[ShippingOption](../types/ShippingOption.md)\], \];<a name="payments_validatedRequestedInfo"></a>  

***
<br><br>[$peerChannel](../constructors/peerChannel.md) = \['channel_id' => [int](../types/int.md), \];<a name="peerChannel"></a>  

***
<br><br>[$peerChat](../constructors/peerChat.md) = \['chat_id' => [int](../types/int.md), \];<a name="peerChat"></a>  

***
<br><br>[$peerNotifyEventsAll](../constructors/peerNotifyEventsAll.md) = \[\];<a name="peerNotifyEventsAll"></a>  

***
<br><br>[$peerNotifyEventsEmpty](../constructors/peerNotifyEventsEmpty.md) = \[\];<a name="peerNotifyEventsEmpty"></a>  

***
<br><br>[$peerNotifySettings](../constructors/peerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];<a name="peerNotifySettings"></a>  

***
<br><br>[$peerNotifySettingsEmpty](../constructors/peerNotifySettingsEmpty.md) = \[\];<a name="peerNotifySettingsEmpty"></a>  

***
<br><br>[$peerSettings](../constructors/peerSettings.md) = \['report_spam' => [Bool](../types/Bool.md), \];<a name="peerSettings"></a>  

***
<br><br>[$peerUser](../constructors/peerUser.md) = \['user_id' => [int](../types/int.md), \];<a name="peerUser"></a>  

***
<br><br>[$phoneCall](../constructors/phoneCall.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a_or_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), 'protocol' => [PhoneCallProtocol](../types/PhoneCallProtocol.md), 'connection' => [PhoneConnection](../types/PhoneConnection.md), 'alternative_connections' => \[[PhoneConnection](../types/PhoneConnection.md)\], 'start_date' => [int](../types/int.md), \];<a name="phoneCall"></a>  

***
<br><br>[$phoneCallAccepted](../constructors/phoneCallAccepted.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_b' => [bytes](../types/bytes.md), 'protocol' => [PhoneCallProtocol](../types/PhoneCallProtocol.md), \];<a name="phoneCallAccepted"></a>  

***
<br><br>[$phoneCallDiscardReasonBusy](../constructors/phoneCallDiscardReasonBusy.md) = \[\];<a name="phoneCallDiscardReasonBusy"></a>  

***
<br><br>[$phoneCallDiscardReasonDisconnect](../constructors/phoneCallDiscardReasonDisconnect.md) = \[\];<a name="phoneCallDiscardReasonDisconnect"></a>  

***
<br><br>[$phoneCallDiscardReasonHangup](../constructors/phoneCallDiscardReasonHangup.md) = \[\];<a name="phoneCallDiscardReasonHangup"></a>  

***
<br><br>[$phoneCallDiscardReasonMissed](../constructors/phoneCallDiscardReasonMissed.md) = \[\];<a name="phoneCallDiscardReasonMissed"></a>  

***
<br><br>[$phoneCallDiscarded](../constructors/phoneCallDiscarded.md) = \['need_rating' => [Bool](../types/Bool.md), 'need_debug' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'reason' => [PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md), 'duration' => [int](../types/int.md), \];<a name="phoneCallDiscarded"></a>  

***
<br><br>[$phoneCallEmpty](../constructors/phoneCallEmpty.md) = \['id' => [long](../types/long.md), \];<a name="phoneCallEmpty"></a>  

***
<br><br>[$phoneCallProtocol](../constructors/phoneCallProtocol.md) = \['udp_p2p' => [Bool](../types/Bool.md), 'udp_reflector' => [Bool](../types/Bool.md), 'min_layer' => [int](../types/int.md), 'max_layer' => [int](../types/int.md), \];<a name="phoneCallProtocol"></a>  

***
<br><br>[$phoneCallRequested](../constructors/phoneCallRequested.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a_hash' => [bytes](../types/bytes.md), 'protocol' => [PhoneCallProtocol](../types/PhoneCallProtocol.md), \];<a name="phoneCallRequested"></a>  

***
<br><br>[$phoneCallWaiting](../constructors/phoneCallWaiting.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'protocol' => [PhoneCallProtocol](../types/PhoneCallProtocol.md), 'receive_date' => [int](../types/int.md), \];<a name="phoneCallWaiting"></a>  

***
<br><br>[$phoneConnection](../constructors/phoneConnection.md) = \['id' => [long](../types/long.md), 'ip' => [string](../types/string.md), 'ipv6' => [string](../types/string.md), 'port' => [int](../types/int.md), 'peer_tag' => [bytes](../types/bytes.md), \];<a name="phoneConnection"></a>  

***
<br><br>[$phone\_phoneCall](../constructors/phone_phoneCall.md) = \['phone_call' => [PhoneCall](../types/PhoneCall.md), 'users' => \[[User](../types/User.md)\], \];<a name="phone_phoneCall"></a>  

***
<br><br>[$photo](../constructors/photo.md) = \['has_stickers' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], \];<a name="photo"></a>  

***
<br><br>[$photoCachedSize](../constructors/photoCachedSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="photoCachedSize"></a>  

***
<br><br>[$photoEmpty](../constructors/photoEmpty.md) = \['id' => [long](../types/long.md), \];<a name="photoEmpty"></a>  

***
<br><br>[$photoSize](../constructors/photoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'size' => [int](../types/int.md), \];<a name="photoSize"></a>  

***
<br><br>[$photoSizeEmpty](../constructors/photoSizeEmpty.md) = \['type' => [string](../types/string.md), \];<a name="photoSizeEmpty"></a>  

***
<br><br>[$photos\_photo](../constructors/photos_photo.md) = \['photo' => [Photo](../types/Photo.md), 'users' => \[[User](../types/User.md)\], \];<a name="photos_photo"></a>  

[$photos\_photos](../constructors/photos_photos.md) = \['photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="photos_photos"></a>  

[$photos\_photosSlice](../constructors/photos_photosSlice.md) = \['count' => [int](../types/int.md), 'photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="photos_photosSlice"></a>  

***
<br><br>[$pong](../constructors/pong.md) = \['msg_id' => [long](../types/long.md), 'ping_id' => [long](../types/long.md), \];<a name="pong"></a>  

***
<br><br>[$postAddress](../constructors/postAddress.md) = \['street_line1' => [string](../types/string.md), 'street_line2' => [string](../types/string.md), 'city' => [string](../types/string.md), 'state' => [string](../types/string.md), 'country_iso2' => [string](../types/string.md), 'post_code' => [string](../types/string.md), \];<a name="postAddress"></a>  

***
<br><br>[$privacyKeyChatInvite](../constructors/privacyKeyChatInvite.md) = \[\];<a name="privacyKeyChatInvite"></a>  

***
<br><br>[$privacyKeyPhoneCall](../constructors/privacyKeyPhoneCall.md) = \[\];<a name="privacyKeyPhoneCall"></a>  

***
<br><br>[$privacyKeyStatusTimestamp](../constructors/privacyKeyStatusTimestamp.md) = \[\];<a name="privacyKeyStatusTimestamp"></a>  

***
<br><br>[$privacyValueAllowAll](../constructors/privacyValueAllowAll.md) = \[\];<a name="privacyValueAllowAll"></a>  

***
<br><br>[$privacyValueAllowContacts](../constructors/privacyValueAllowContacts.md) = \[\];<a name="privacyValueAllowContacts"></a>  

***
<br><br>[$privacyValueAllowUsers](../constructors/privacyValueAllowUsers.md) = \['users' => \[[int](../types/int.md)\], \];<a name="privacyValueAllowUsers"></a>  

***
<br><br>[$privacyValueDisallowAll](../constructors/privacyValueDisallowAll.md) = \[\];<a name="privacyValueDisallowAll"></a>  

***
<br><br>[$privacyValueDisallowContacts](../constructors/privacyValueDisallowContacts.md) = \[\];<a name="privacyValueDisallowContacts"></a>  

***
<br><br>[$privacyValueDisallowUsers](../constructors/privacyValueDisallowUsers.md) = \['users' => \[[int](../types/int.md)\], \];<a name="privacyValueDisallowUsers"></a>  

***
<br><br>[$receivedNotifyMessage](../constructors/receivedNotifyMessage.md) = \['id' => [int](../types/int.md), \];<a name="receivedNotifyMessage"></a>  

***
<br><br>[$replyInlineMarkup](../constructors/replyInlineMarkup.md) = \['rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];<a name="replyInlineMarkup"></a>  

***
<br><br>[$replyKeyboardForceReply](../constructors/replyKeyboardForceReply.md) = \['single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \];<a name="replyKeyboardForceReply"></a>  

***
<br><br>[$replyKeyboardHide](../constructors/replyKeyboardHide.md) = \['selective' => [Bool](../types/Bool.md), \];<a name="replyKeyboardHide"></a>  

***
<br><br>[$replyKeyboardMarkup](../constructors/replyKeyboardMarkup.md) = \['resize' => [Bool](../types/Bool.md), 'single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];<a name="replyKeyboardMarkup"></a>  

***
<br><br>[$resPQ](../constructors/resPQ.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'pq' => [string](../types/string.md), 'server_public_key_fingerprints' => \[[long](../types/long.md)\], \];<a name="resPQ"></a>  

***
<br><br>[$rpc\_answer\_dropped](../constructors/rpc_answer_dropped.md) = \['msg_id' => [long](../types/long.md), 'seq_no' => [int](../types/int.md), 'bytes' => [int](../types/int.md), \];<a name="rpc_answer_dropped"></a>  

[$rpc\_answer\_dropped\_running](../constructors/rpc_answer_dropped_running.md) = \[\];<a name="rpc_answer_dropped_running"></a>  

[$rpc\_answer\_unknown](../constructors/rpc_answer_unknown.md) = \[\];<a name="rpc_answer_unknown"></a>  

[$rpc\_error](../constructors/rpc_error.md) = \['error_code' => [int](../types/int.md), 'error_message' => [string](../types/string.md), \];<a name="rpc_error"></a>  

***
<br><br>[$sendMessageCancelAction](../constructors/sendMessageCancelAction.md) = \[\];<a name="sendMessageCancelAction"></a>  

***
<br><br>[$sendMessageChooseContactAction](../constructors/sendMessageChooseContactAction.md) = \[\];<a name="sendMessageChooseContactAction"></a>  

***
<br><br>[$sendMessageGamePlayAction](../constructors/sendMessageGamePlayAction.md) = \[\];<a name="sendMessageGamePlayAction"></a>  

***
<br><br>[$sendMessageGeoLocationAction](../constructors/sendMessageGeoLocationAction.md) = \[\];<a name="sendMessageGeoLocationAction"></a>  

***
<br><br>[$sendMessageRecordAudioAction](../constructors/sendMessageRecordAudioAction.md) = \[\];<a name="sendMessageRecordAudioAction"></a>  

***
<br><br>[$sendMessageRecordRoundAction](../constructors/sendMessageRecordRoundAction.md) = \[\];<a name="sendMessageRecordRoundAction"></a>  

***
<br><br>[$sendMessageRecordVideoAction](../constructors/sendMessageRecordVideoAction.md) = \[\];<a name="sendMessageRecordVideoAction"></a>  

***
<br><br>[$sendMessageTypingAction](../constructors/sendMessageTypingAction.md) = \[\];<a name="sendMessageTypingAction"></a>  

***
<br><br>[$sendMessageUploadAudioAction](../constructors/sendMessageUploadAudioAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadAudioAction"></a>  

***
<br><br>[$sendMessageUploadDocumentAction](../constructors/sendMessageUploadDocumentAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadDocumentAction"></a>  

***
<br><br>[$sendMessageUploadPhotoAction](../constructors/sendMessageUploadPhotoAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadPhotoAction"></a>  

***
<br><br>[$sendMessageUploadRoundAction](../constructors/sendMessageUploadRoundAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadRoundAction"></a>  

***
<br><br>[$sendMessageUploadVideoAction](../constructors/sendMessageUploadVideoAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadVideoAction"></a>  

***
<br><br>[$server\_DH\_inner\_data](../constructors/server_DH_inner_data.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'g' => [int](../types/int.md), 'dh_prime' => [string](../types/string.md), 'g_a' => [string](../types/string.md), 'server_time' => [int](../types/int.md), \];<a name="server_DH_inner_data"></a>  

[$server\_DH\_params\_fail](../constructors/server_DH_params_fail.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash' => [int128](../types/int128.md), \];<a name="server_DH_params_fail"></a>  

[$server\_DH\_params\_ok](../constructors/server_DH_params_ok.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'encrypted_answer' => [string](../types/string.md), \];<a name="server_DH_params_ok"></a>  

***
<br><br>[$shippingOption](../constructors/shippingOption.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'prices' => \[[LabeledPrice](../types/LabeledPrice.md)\], \];<a name="shippingOption"></a>  

***
<br><br>[$stickerPack](../constructors/stickerPack.md) = \['emoticon' => [string](../types/string.md), 'documents' => \[[long](../types/long.md)\], \];<a name="stickerPack"></a>  

***
<br><br>[$stickerSet](../constructors/stickerSet.md) = \['installed' => [Bool](../types/Bool.md), 'archived' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'masks' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \];<a name="stickerSet"></a>  

***
<br><br>[$stickerSetCovered](../constructors/stickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'cover' => [Document](../types/Document.md), \];<a name="stickerSetCovered"></a>  

***
<br><br>[$stickerSetMultiCovered](../constructors/stickerSetMultiCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'covers' => \[[Document](../types/Document.md)\], \];<a name="stickerSetMultiCovered"></a>  

***
<br><br>[$storage\_fileGif](../constructors/storage_fileGif.md) = \[\];<a name="storage_fileGif"></a>  

[$storage\_fileJpeg](../constructors/storage_fileJpeg.md) = \[\];<a name="storage_fileJpeg"></a>  

[$storage\_fileMov](../constructors/storage_fileMov.md) = \[\];<a name="storage_fileMov"></a>  

[$storage\_fileMp3](../constructors/storage_fileMp3.md) = \[\];<a name="storage_fileMp3"></a>  

[$storage\_fileMp4](../constructors/storage_fileMp4.md) = \[\];<a name="storage_fileMp4"></a>  

[$storage\_filePartial](../constructors/storage_filePartial.md) = \[\];<a name="storage_filePartial"></a>  

[$storage\_filePdf](../constructors/storage_filePdf.md) = \[\];<a name="storage_filePdf"></a>  

[$storage\_filePng](../constructors/storage_filePng.md) = \[\];<a name="storage_filePng"></a>  

[$storage\_fileUnknown](../constructors/storage_fileUnknown.md) = \[\];<a name="storage_fileUnknown"></a>  

[$storage\_fileWebp](../constructors/storage_fileWebp.md) = \[\];<a name="storage_fileWebp"></a>  

***
<br><br>[$textBold](../constructors/textBold.md) = \['text' => [RichText](../types/RichText.md), \];<a name="textBold"></a>  

***
<br><br>[$textConcat](../constructors/textConcat.md) = \['texts' => \[[RichText](../types/RichText.md)\], \];<a name="textConcat"></a>  

***
<br><br>[$textEmail](../constructors/textEmail.md) = \['text' => [RichText](../types/RichText.md), 'email' => [string](../types/string.md), \];<a name="textEmail"></a>  

***
<br><br>[$textEmpty](../constructors/textEmpty.md) = \[\];<a name="textEmpty"></a>  

***
<br><br>[$textFixed](../constructors/textFixed.md) = \['text' => [RichText](../types/RichText.md), \];<a name="textFixed"></a>  

***
<br><br>[$textItalic](../constructors/textItalic.md) = \['text' => [RichText](../types/RichText.md), \];<a name="textItalic"></a>  

***
<br><br>[$textPlain](../constructors/textPlain.md) = \['text' => [string](../types/string.md), \];<a name="textPlain"></a>  

***
<br><br>[$textStrike](../constructors/textStrike.md) = \['text' => [RichText](../types/RichText.md), \];<a name="textStrike"></a>  

***
<br><br>[$textUnderline](../constructors/textUnderline.md) = \['text' => [RichText](../types/RichText.md), \];<a name="textUnderline"></a>  

***
<br><br>[$textUrl](../constructors/textUrl.md) = \['text' => [RichText](../types/RichText.md), 'url' => [string](../types/string.md), 'webpage_id' => [long](../types/long.md), \];<a name="textUrl"></a>  

***
<br><br>[$topPeer](../constructors/topPeer.md) = \['peer' => [Peer](../types/Peer.md), 'rating' => [double](../types/double.md), \];<a name="topPeer"></a>  

***
<br><br>[$topPeerCategoryBotsInline](../constructors/topPeerCategoryBotsInline.md) = \[\];<a name="topPeerCategoryBotsInline"></a>  

***
<br><br>[$topPeerCategoryBotsPM](../constructors/topPeerCategoryBotsPM.md) = \[\];<a name="topPeerCategoryBotsPM"></a>  

***
<br><br>[$topPeerCategoryChannels](../constructors/topPeerCategoryChannels.md) = \[\];<a name="topPeerCategoryChannels"></a>  

***
<br><br>[$topPeerCategoryCorrespondents](../constructors/topPeerCategoryCorrespondents.md) = \[\];<a name="topPeerCategoryCorrespondents"></a>  

***
<br><br>[$topPeerCategoryGroups](../constructors/topPeerCategoryGroups.md) = \[\];<a name="topPeerCategoryGroups"></a>  

***
<br><br>[$topPeerCategoryPeers](../constructors/topPeerCategoryPeers.md) = \['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'count' => [int](../types/int.md), 'peers' => \[[TopPeer](../types/TopPeer.md)\], \];<a name="topPeerCategoryPeers"></a>  

***
<br><br>[$topPeerCategoryPhoneCalls](../constructors/topPeerCategoryPhoneCalls.md) = \[\];<a name="topPeerCategoryPhoneCalls"></a>  

***
<br><br>[$true](../constructors/true.md) = \[\];<a name="true"></a>  

***
<br><br>[$updateBotCallbackQuery](../constructors/updateBotCallbackQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'peer' => [Peer](../types/Peer.md), 'msg_id' => [int](../types/int.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];<a name="updateBotCallbackQuery"></a>  

***
<br><br>[$updateBotInlineQuery](../constructors/updateBotInlineQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'offset' => [string](../types/string.md), \];<a name="updateBotInlineQuery"></a>  

***
<br><br>[$updateBotInlineSend](../constructors/updateBotInlineSend.md) = \['user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'id' => [string](../types/string.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), \];<a name="updateBotInlineSend"></a>  

***
<br><br>[$updateBotPrecheckoutQuery](../constructors/updateBotPrecheckoutQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'payload' => [bytes](../types/bytes.md), 'info' => [PaymentRequestedInfo](../types/PaymentRequestedInfo.md), 'shipping_option_id' => [string](../types/string.md), 'currency' => [string](../types/string.md), 'total_amount' => [long](../types/long.md), \];<a name="updateBotPrecheckoutQuery"></a>  

***
<br><br>[$updateBotShippingQuery](../constructors/updateBotShippingQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'payload' => [bytes](../types/bytes.md), 'shipping_address' => [PostAddress](../types/PostAddress.md), \];<a name="updateBotShippingQuery"></a>  

***
<br><br>[$updateBotWebhookJSON](../constructors/updateBotWebhookJSON.md) = \['data' => [DataJSON](../types/DataJSON.md), \];<a name="updateBotWebhookJSON"></a>  

***
<br><br>[$updateBotWebhookJSONQuery](../constructors/updateBotWebhookJSONQuery.md) = \['query_id' => [long](../types/long.md), 'data' => [DataJSON](../types/DataJSON.md), 'timeout' => [int](../types/int.md), \];<a name="updateBotWebhookJSONQuery"></a>  

***
<br><br>[$updateChannel](../constructors/updateChannel.md) = \['channel_id' => [int](../types/int.md), \];<a name="updateChannel"></a>  

***
<br><br>[$updateChannelMessageViews](../constructors/updateChannelMessageViews.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \];<a name="updateChannelMessageViews"></a>  

***
<br><br>[$updateChannelPinnedMessage](../constructors/updateChannelPinnedMessage.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), \];<a name="updateChannelPinnedMessage"></a>  

***
<br><br>[$updateChannelTooLong](../constructors/updateChannelTooLong.md) = \['channel_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), \];<a name="updateChannelTooLong"></a>  

***
<br><br>[$updateChannelWebPage](../constructors/updateChannelWebPage.md) = \['channel_id' => [int](../types/int.md), 'webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateChannelWebPage"></a>  

***
<br><br>[$updateChatAdmins](../constructors/updateChatAdmins.md) = \['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];<a name="updateChatAdmins"></a>  

***
<br><br>[$updateChatParticipantAdd](../constructors/updateChatParticipantAdd.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \];<a name="updateChatParticipantAdd"></a>  

***
<br><br>[$updateChatParticipantAdmin](../constructors/updateChatParticipantAdmin.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'is_admin' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];<a name="updateChatParticipantAdmin"></a>  

***
<br><br>[$updateChatParticipantDelete](../constructors/updateChatParticipantDelete.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'version' => [int](../types/int.md), \];<a name="updateChatParticipantDelete"></a>  

***
<br><br>[$updateChatParticipants](../constructors/updateChatParticipants.md) = \['participants' => [ChatParticipants](../types/ChatParticipants.md), \];<a name="updateChatParticipants"></a>  

***
<br><br>[$updateChatUserTyping](../constructors/updateChatUserTyping.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];<a name="updateChatUserTyping"></a>  

***
<br><br>[$updateConfig](../constructors/updateConfig.md) = \[\];<a name="updateConfig"></a>  

***
<br><br>[$updateContactLink](../constructors/updateContactLink.md) = \['user_id' => [int](../types/int.md), 'my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), \];<a name="updateContactLink"></a>  

***
<br><br>[$updateContactRegistered](../constructors/updateContactRegistered.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="updateContactRegistered"></a>  

***
<br><br>[$updateDcOptions](../constructors/updateDcOptions.md) = \['dc_options' => \[[DcOption](../types/DcOption.md)\], \];<a name="updateDcOptions"></a>  

***
<br><br>[$updateDeleteChannelMessages](../constructors/updateDeleteChannelMessages.md) = \['channel_id' => [int](../types/int.md), 'messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateDeleteChannelMessages"></a>  

***
<br><br>[$updateDeleteMessages](../constructors/updateDeleteMessages.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateDeleteMessages"></a>  

***
<br><br>[$updateDialogPinned](../constructors/updateDialogPinned.md) = \['pinned' => [Bool](../types/Bool.md), 'peer' => [Peer](../types/Peer.md), \];<a name="updateDialogPinned"></a>  

***
<br><br>[$updateDraftMessage](../constructors/updateDraftMessage.md) = \['peer' => [Peer](../types/Peer.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];<a name="updateDraftMessage"></a>  

***
<br><br>[$updateEditChannelMessage](../constructors/updateEditChannelMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateEditChannelMessage"></a>  

***
<br><br>[$updateEditMessage](../constructors/updateEditMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateEditMessage"></a>  

***
<br><br>[$updateEncryptedChatTyping](../constructors/updateEncryptedChatTyping.md) = \['chat_id' => [int](../types/int.md), \];<a name="updateEncryptedChatTyping"></a>  

***
<br><br>[$updateEncryptedMessagesRead](../constructors/updateEncryptedMessagesRead.md) = \['chat_id' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="updateEncryptedMessagesRead"></a>  

***
<br><br>[$updateEncryption](../constructors/updateEncryption.md) = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \];<a name="updateEncryption"></a>  

***
<br><br>[$updateInlineBotCallbackQuery](../constructors/updateInlineBotCallbackQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];<a name="updateInlineBotCallbackQuery"></a>  

***
<br><br>[$updateLangPack](../constructors/updateLangPack.md) = \['difference' => [LangPackDifference](../types/LangPackDifference.md), \];<a name="updateLangPack"></a>  

***
<br><br>[$updateLangPackTooLong](../constructors/updateLangPackTooLong.md) = \[\];<a name="updateLangPackTooLong"></a>  

***
<br><br>[$updateMessageID](../constructors/updateMessageID.md) = \['id' => [int](../types/int.md), \];<a name="updateMessageID"></a>  

***
<br><br>[$updateNewChannelMessage](../constructors/updateNewChannelMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateNewChannelMessage"></a>  

***
<br><br>[$updateNewEncryptedMessage](../constructors/updateNewEncryptedMessage.md) = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \];<a name="updateNewEncryptedMessage"></a>  

***
<br><br>[$updateNewMessage](../constructors/updateNewMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateNewMessage"></a>  

***
<br><br>[$updateNewStickerSet](../constructors/updateNewStickerSet.md) = \['stickerset' => [messages\_StickerSet](../types/messages_StickerSet.md), \];<a name="updateNewStickerSet"></a>  

***
<br><br>[$updateNotifySettings](../constructors/updateNotifySettings.md) = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];<a name="updateNotifySettings"></a>  

***
<br><br>[$updatePhoneCall](../constructors/updatePhoneCall.md) = \['phone_call' => [PhoneCall](../types/PhoneCall.md), \];<a name="updatePhoneCall"></a>  

***
<br><br>[$updatePinnedDialogs](../constructors/updatePinnedDialogs.md) = \['order' => \[[Peer](../types/Peer.md)\], \];<a name="updatePinnedDialogs"></a>  

***
<br><br>[$updatePrivacy](../constructors/updatePrivacy.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];<a name="updatePrivacy"></a>  

***
<br><br>[$updatePtsChanged](../constructors/updatePtsChanged.md) = \[\];<a name="updatePtsChanged"></a>  

***
<br><br>[$updateReadChannelInbox](../constructors/updateReadChannelInbox.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];<a name="updateReadChannelInbox"></a>  

***
<br><br>[$updateReadChannelOutbox](../constructors/updateReadChannelOutbox.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];<a name="updateReadChannelOutbox"></a>  

***
<br><br>[$updateReadFeaturedStickers](../constructors/updateReadFeaturedStickers.md) = \[\];<a name="updateReadFeaturedStickers"></a>  

***
<br><br>[$updateReadHistoryInbox](../constructors/updateReadHistoryInbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadHistoryInbox"></a>  

***
<br><br>[$updateReadHistoryOutbox](../constructors/updateReadHistoryOutbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadHistoryOutbox"></a>  

***
<br><br>[$updateReadMessagesContents](../constructors/updateReadMessagesContents.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadMessagesContents"></a>  

***
<br><br>[$updateRecentStickers](../constructors/updateRecentStickers.md) = \[\];<a name="updateRecentStickers"></a>  

***
<br><br>[$updateSavedGifs](../constructors/updateSavedGifs.md) = \[\];<a name="updateSavedGifs"></a>  

***
<br><br>[$updateServiceNotification](../constructors/updateServiceNotification.md) = \['popup' => [Bool](../types/Bool.md), 'inbox_date' => [int](../types/int.md), 'type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateServiceNotification"></a>  

***
<br><br>[$updateShort](../constructors/updateShort.md) = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \];<a name="updateShort"></a>  

***
<br><br>[$updateShortChatMessage](../constructors/updateShortChatMessage.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'chat_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortChatMessage"></a>  

***
<br><br>[$updateShortMessage](../constructors/updateShortMessage.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortMessage"></a>  

***
<br><br>[$updateShortSentMessage](../constructors/updateShortSentMessage.md) = \['out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortSentMessage"></a>  

***
<br><br>[$updateStickerSets](../constructors/updateStickerSets.md) = \[\];<a name="updateStickerSets"></a>  

***
<br><br>[$updateStickerSetsOrder](../constructors/updateStickerSetsOrder.md) = \['masks' => [Bool](../types/Bool.md), 'order' => \[[long](../types/long.md)\], \];<a name="updateStickerSetsOrder"></a>  

***
<br><br>[$updateUserBlocked](../constructors/updateUserBlocked.md) = \['user_id' => [int](../types/int.md), 'blocked' => [Bool](../types/Bool.md), \];<a name="updateUserBlocked"></a>  

***
<br><br>[$updateUserName](../constructors/updateUserName.md) = \['user_id' => [int](../types/int.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), \];<a name="updateUserName"></a>  

***
<br><br>[$updateUserPhone](../constructors/updateUserPhone.md) = \['user_id' => [int](../types/int.md), 'phone' => [string](../types/string.md), \];<a name="updateUserPhone"></a>  

***
<br><br>[$updateUserPhoto](../constructors/updateUserPhoto.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'previous' => [Bool](../types/Bool.md), \];<a name="updateUserPhoto"></a>  

***
<br><br>[$updateUserStatus](../constructors/updateUserStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];<a name="updateUserStatus"></a>  

***
<br><br>[$updateUserTyping](../constructors/updateUserTyping.md) = \['user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];<a name="updateUserTyping"></a>  

***
<br><br>[$updateWebPage](../constructors/updateWebPage.md) = \['webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateWebPage"></a>  

***
<br><br>[$updates](../constructors/updates.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];<a name="updates"></a>  

***
<br><br>[$updatesCombined](../constructors/updatesCombined.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq_start' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];<a name="updatesCombined"></a>  

***
<br><br>[$updatesTooLong](../constructors/updatesTooLong.md) = \[\];<a name="updatesTooLong"></a>  

***
<br><br>[$updates\_channelDifference](../constructors/updates_channelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'new_messages' => \[[Message](../types/Message.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="updates_channelDifference"></a>  

[$updates\_channelDifferenceEmpty](../constructors/updates_channelDifferenceEmpty.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), \];<a name="updates_channelDifferenceEmpty"></a>  

[$updates\_channelDifferenceTooLong](../constructors/updates_channelDifferenceTooLong.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="updates_channelDifferenceTooLong"></a>  

[$updates\_difference](../constructors/updates_difference.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];<a name="updates_difference"></a>  

[$updates\_differenceEmpty](../constructors/updates_differenceEmpty.md) = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];<a name="updates_differenceEmpty"></a>  

[$updates\_differenceSlice](../constructors/updates_differenceSlice.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'intermediate_state' => [updates\_State](../types/updates_State.md), \];<a name="updates_differenceSlice"></a>  

[$updates\_differenceTooLong](../constructors/updates_differenceTooLong.md) = \['pts' => [int](../types/int.md), \];<a name="updates_differenceTooLong"></a>  

[$updates\_state](../constructors/updates_state.md) = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), \];<a name="updates_state"></a>  

***
<br><br>[$upload\_cdnFile](../constructors/upload_cdnFile.md) = \['bytes' => [bytes](../types/bytes.md), \];<a name="upload_cdnFile"></a>  

[$upload\_cdnFileReuploadNeeded](../constructors/upload_cdnFileReuploadNeeded.md) = \['request_token' => [bytes](../types/bytes.md), \];<a name="upload_cdnFileReuploadNeeded"></a>  

[$upload\_file](../constructors/upload_file.md) = \['type' => [storage\_FileType](../types/storage_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="upload_file"></a>  

[$upload\_fileCdnRedirect](../constructors/upload_fileCdnRedirect.md) = \['dc_id' => [int](../types/int.md), 'file_token' => [bytes](../types/bytes.md), 'encryption_key' => [bytes](../types/bytes.md), 'encryption_iv' => [bytes](../types/bytes.md), \];<a name="upload_fileCdnRedirect"></a>  

[$upload\_webFile](../constructors/upload_webFile.md) = \['size' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'file_type' => [storage\_FileType](../types/storage_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="upload_webFile"></a>  

***
<br><br>[$user](../constructors/user.md) = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot_chat_history' => [Bool](../types/Bool.md), 'bot_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'bot_inline_geo' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot_info_version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), 'bot_inline_placeholder' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \];<a name="user"></a>  

***
<br><br>[$userEmpty](../constructors/userEmpty.md) = \['id' => [int](../types/int.md), \];<a name="userEmpty"></a>  

***
<br><br>[$userFull](../constructors/userFull.md) = \['blocked' => [Bool](../types/Bool.md), 'phone_calls_available' => [Bool](../types/Bool.md), 'phone_calls_private' => [Bool](../types/Bool.md), 'user' => [User](../types/User.md), 'about' => [string](../types/string.md), 'link' => [contacts\_Link](../types/contacts_Link.md), 'profile_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'bot_info' => [BotInfo](../types/BotInfo.md), 'common_chats_count' => [int](../types/int.md), \];<a name="userFull"></a>  

***
<br><br>[$userProfilePhoto](../constructors/userProfilePhoto.md) = \['photo_id' => [long](../types/long.md), 'photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];<a name="userProfilePhoto"></a>  

***
<br><br>[$userProfilePhotoEmpty](../constructors/userProfilePhotoEmpty.md) = \[\];<a name="userProfilePhotoEmpty"></a>  

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
<br><br>[$wallPaper](../constructors/wallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], 'color' => [int](../types/int.md), \];<a name="wallPaper"></a>  

***
<br><br>[$wallPaperSolid](../constructors/wallPaperSolid.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \];<a name="wallPaperSolid"></a>  

***
<br><br>[$webDocument](../constructors/webDocument.md) = \['url' => [string](../types/string.md), 'access_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'dc_id' => [int](../types/int.md), \];<a name="webDocument"></a>  

***
<br><br>[$webPage](../constructors/webPage.md) = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'hash' => [int](../types/int.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), 'cached_page' => [Page](../types/Page.md), \];<a name="webPage"></a>  

***
<br><br>[$webPageEmpty](../constructors/webPageEmpty.md) = \['id' => [long](../types/long.md), \];<a name="webPageEmpty"></a>  

***
<br><br>[$webPageNotModified](../constructors/webPageNotModified.md) = \[\];<a name="webPageNotModified"></a>  

***
<br><br>[$webPagePending](../constructors/webPagePending.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \];<a name="webPagePending"></a>  

