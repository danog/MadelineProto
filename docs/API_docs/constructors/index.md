# Constructors  

[$AccountDaysTTL](../types/AccountDaysTTL.md) = \['days' => [int](../types/int.md), \];  

[$account\_Authorizations](../types/account_Authorizations.md) = \['authorizations' => \[[Authorization](../types/Authorization.md)\], \];  

[$account\_Password](../types/account_Password.md) = \['new_salt' => [bytes](../types/bytes.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];  

[$account\_Password](../types/account_Password.md) = \['current_salt' => [bytes](../types/bytes.md), 'new_salt' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'has_recovery' => [Bool](../types/Bool.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];  

[$account\_PasswordInputSettings](../types/account_PasswordInputSettings.md) = \['new_salt' => [bytes](../types/bytes.md), 'new_password_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'email' => [string](../types/string.md), \];  

[$account\_PasswordSettings](../types/account_PasswordSettings.md) = \['email' => [string](../types/string.md), \];  

[$account\_PrivacyRules](../types/account_PrivacyRules.md) = \['rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$auth\_Authorization](../types/auth_Authorization.md) = \['tmp_sessions' => [int](../types/int.md), 'user' => [User](../types/User.md), \];  

[$auth\_CheckedPhone](../types/auth_CheckedPhone.md) = \['phone_registered' => [Bool](../types/Bool.md), \];  

[$auth\_CodeType](../types/auth_CodeType.md) = \[\];  

[$auth\_CodeType](../types/auth_CodeType.md) = \[\];  

[$auth\_CodeType](../types/auth_CodeType.md) = \[\];  

[$auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md) = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$auth\_PasswordRecovery](../types/auth_PasswordRecovery.md) = \['email_pattern' => [string](../types/string.md), \];  

[$auth\_SentCode](../types/auth_SentCode.md) = \['phone_registered' => [Bool](../types/Bool.md), 'type' => [auth\_SentCodeType](../types/auth_SentCodeType.md), 'phone_code_hash' => [string](../types/string.md), 'next_type' => [auth\_CodeType](../types/auth_CodeType.md), 'timeout' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth_SentCodeType.md) = \['pattern' => [string](../types/string.md), \];  

[$auth\_SentCodeType](../types/auth_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$Authorization](../types/Authorization.md) = \['hash' => [long](../types/long.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'api_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'date_created' => [int](../types/int.md), 'date_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];  

[$Bool](../types/Bool.md) = \[\];  

[$Bool](../types/Bool.md) = \[\];  

[$BotCommand](../types/BotCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$BotInfo](../types/BotInfo.md) = \['user_id' => [int](../types/int.md), 'description' => [string](../types/string.md), 'commands' => \[[BotCommand](../types/BotCommand.md)\], \];  

[$BotInlineResult](../types/BotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineResult](../types/BotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$Chat](../types/Chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'editor' => [Bool](../types/Bool.md), 'moderator' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'democracy' => [Bool](../types/Bool.md), 'signatures' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), \];  

[$Chat](../types/Chat.md) = \['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), \];  

[$ChatFull](../types/ChatFull.md) = \['can_view_participants' => [Bool](../types/Bool.md), 'can_set_username' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants_count' => [int](../types/int.md), 'admins_count' => [int](../types/int.md), 'kicked_count' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], 'migrated_from_chat_id' => [int](../types/int.md), 'migrated_from_max_id' => [int](../types/int.md), 'pinned_msg_id' => [int](../types/int.md), \];  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md) = \['exclude_new_messages' => [Bool](../types/Bool.md), 'ranges' => \[[MessageRange](../types/MessageRange.md)\], \];  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md) = \[\];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), 'kicked_by' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$channels\_ChannelParticipant](../types/channels_ChannelParticipant.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => \[[User](../types/User.md)\], \];  

[$channels\_ChannelParticipants](../types/channels_ChannelParticipants.md) = \['count' => [int](../types/int.md), 'participants' => \[[ChannelParticipant](../types/ChannelParticipant.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$Chat](../types/Chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'admins_enabled' => [Bool](../types/Bool.md), 'admin' => [Bool](../types/Bool.md), 'deactivated' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'migrated_to' => [InputChannel](../types/InputChannel.md), \];  

[$Chat](../types/Chat.md) = \['id' => [int](../types/int.md), \];  

[$Chat](../types/Chat.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), \];  

[$ChatFull](../types/ChatFull.md) = \['id' => [int](../types/int.md), 'participants' => [ChatParticipants](../types/ChatParticipants.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], \];  

[$ChatInvite](../types/ChatInvite.md) = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'participants' => \[[User](../types/User.md)\], \];  

[$ChatInvite](../types/ChatInvite.md) = \['chat' => [Chat](../types/Chat.md), \];  

[$ExportedChatInvite](../types/ExportedChatInvite.md) = \[\];  

[$ExportedChatInvite](../types/ExportedChatInvite.md) = \['link' => [string](../types/string.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user_id' => [int](../types/int.md), \];  

[$ChatParticipants](../types/ChatParticipants.md) = \['chat_id' => [int](../types/int.md), 'participants' => \[[ChatParticipant](../types/ChatParticipant.md)\], 'version' => [int](../types/int.md), \];  

[$ChatParticipants](../types/ChatParticipants.md) = \['chat_id' => [int](../types/int.md), 'self_participant' => [ChatParticipant](../types/ChatParticipant.md), \];  

[$ChatPhoto](../types/ChatPhoto.md) = \['photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];  

[$ChatPhoto](../types/ChatPhoto.md) = \[\];  

[$Config](../types/Config.md) = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test_mode' => [Bool](../types/Bool.md), 'this_dc' => [int](../types/int.md), 'dc_options' => \[[DcOption](../types/DcOption.md)\], 'chat_size_max' => [int](../types/int.md), 'megagroup_size_max' => [int](../types/int.md), 'forwarded_count_max' => [int](../types/int.md), 'online_update_period_ms' => [int](../types/int.md), 'offline_blur_timeout_ms' => [int](../types/int.md), 'offline_idle_timeout_ms' => [int](../types/int.md), 'online_cloud_timeout_ms' => [int](../types/int.md), 'notify_cloud_delay_ms' => [int](../types/int.md), 'notify_default_delay_ms' => [int](../types/int.md), 'chat_big_size' => [int](../types/int.md), 'push_chat_period_ms' => [int](../types/int.md), 'push_chat_limit' => [int](../types/int.md), 'saved_gifs_limit' => [int](../types/int.md), 'edit_time_limit' => [int](../types/int.md), 'rating_e_decay' => [int](../types/int.md), 'stickers_recent_limit' => [int](../types/int.md), 'tmp_sessions' => [int](../types/int.md), 'disabled_features' => \[[DisabledFeature](../types/DisabledFeature.md)\], \];  

[$Contact](../types/Contact.md) = \['user_id' => [int](../types/int.md), 'mutual' => [Bool](../types/Bool.md), \];  

[$ContactBlocked](../types/ContactBlocked.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactStatus](../types/ContactStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$contacts\_Blocked](../types/contacts_Blocked.md) = \['blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_Blocked](../types/contacts_Blocked.md) = \['count' => [int](../types/int.md), 'blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_Contacts](../types/contacts_Contacts.md) = \['contacts' => \[[Contact](../types/Contact.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_Contacts](../types/contacts_Contacts.md) = \[\];  

[$contacts\_Found](../types/contacts_Found.md) = \['results' => \[[Peer](../types/Peer.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_ImportedContacts](../types/contacts_ImportedContacts.md) = \['imported' => \[[ImportedContact](../types/ImportedContact.md)\], 'retry_contacts' => \[[long](../types/long.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_Link](../types/contacts_Link.md) = \['my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \];  

[$contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md) = \['peer' => [Peer](../types/Peer.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_TopPeers](../types/contacts_TopPeers.md) = \['categories' => \[[TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_TopPeers](../types/contacts_TopPeers.md) = \[\];  

[$DcOption](../types/DcOption.md) = \['ipv6' => [Bool](../types/Bool.md), 'media_only' => [Bool](../types/Bool.md), 'tcpo_only' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \];  

[$Dialog](../types/Dialog.md) = \['peer' => [Peer](../types/Peer.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$DisabledFeature](../types/DisabledFeature.md) = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$Document](../types/Document.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \[\];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['voice' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'waveform' => [bytes](../types/bytes.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['file_name' => [string](../types/string.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \[\];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['mask' => [Bool](../types/Bool.md), 'alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'mask_coords' => [MaskCoords](../types/MaskCoords.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$Document](../types/Document.md) = \['id' => [long](../types/long.md), \];  

[$DraftMessage](../types/DraftMessage.md) = \['no_webpage' => [Bool](../types/Bool.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'date' => [int](../types/int.md), \];  

[$DraftMessage](../types/DraftMessage.md) = \[\];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a_or_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a' => [bytes](../types/bytes.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), \];  

[$EncryptedFile](../types/EncryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'dc_id' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$EncryptedFile](../types/EncryptedFile.md) = \[\];  

[$EncryptedMessage](../types/EncryptedMessage.md) = \['random_id' => [long](../types/long.md), 'chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$EncryptedMessage](../types/EncryptedMessage.md) = \['random_id' => [long](../types/long.md), 'chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$Error](../types/Error.md) = \['code' => [int](../types/int.md), 'text' => [string](../types/string.md), \];  

[$ExportedMessageLink](../types/ExportedMessageLink.md) = \['link' => [string](../types/string.md), \];  

[$FileLocation](../types/FileLocation.md) = \['dc_id' => [int](../types/int.md), 'volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$FileLocation](../types/FileLocation.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$FoundGif](../types/FoundGif.md) = \['url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$FoundGif](../types/FoundGif.md) = \['url' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$Game](../types/Game.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'short_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$GeoPoint](../types/GeoPoint.md) = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \];  

[$GeoPoint](../types/GeoPoint.md) = \[\];  

[$help\_AppChangelog](../types/help_AppChangelog.md) = \['text' => [string](../types/string.md), \];  

[$help\_AppChangelog](../types/help_AppChangelog.md) = \[\];  

[$help\_AppUpdate](../types/help_AppUpdate.md) = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \];  

[$help\_InviteText](../types/help_InviteText.md) = \['message' => [string](../types/string.md), \];  

[$help\_AppUpdate](../types/help_AppUpdate.md) = \[\];  

[$help\_Support](../types/help_Support.md) = \['phone_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \];  

[$help\_TermsOfService](../types/help_TermsOfService.md) = \['text' => [string](../types/string.md), \];  

[$HighScore](../types/HighScore.md) = \['pos' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];  

[$ImportedContact](../types/ImportedContact.md) = \['user_id' => [int](../types/int.md), 'client_id' => [long](../types/long.md), \];  

[$InlineBotSwitchPM](../types/InlineBotSwitchPM.md) = \['text' => [string](../types/string.md), 'start_param' => [string](../types/string.md), \];  

[$InputAppEvent](../types/InputAppEvent.md) = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessageID](../types/InputBotInlineMessageID.md) = \['dc_id' => [int](../types/int.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document' => [InputDocument](../types/InputDocument.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [InputPhoto](../types/InputPhoto.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputChannel](../types/InputChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$InputChannel](../types/InputChannel.md) = \[\];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \[\];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \['file' => [InputFile](../types/InputFile.md), \];  

[$InputDocument](../types/InputDocument.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputDocument](../types/InputDocument.md) = \[\];  

[$InputFileLocation](../types/InputFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'version' => [int](../types/int.md), \];  

[$InputEncryptedChat](../types/InputEncryptedChat.md) = \['chat_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \[\];  

[$InputFileLocation](../types/InputFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'md5_checksum' => [string](../types/string.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$InputFile](../types/InputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), 'md5_checksum' => [string](../types/string.md), \];  

[$InputFile](../types/InputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), \];  

[$InputFileLocation](../types/InputFileLocation.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$InputGame](../types/InputGame.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputGame](../types/InputGame.md) = \['bot_id' => [InputUser](../types/InputUser.md), 'short_name' => [string](../types/string.md), \];  

[$InputGeoPoint](../types/InputGeoPoint.md) = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \];  

[$InputGeoPoint](../types/InputGeoPoint.md) = \[\];  

[$InputMedia](../types/InputMedia.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputDocument](../types/InputDocument.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \[\];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputGame](../types/InputGame.md), \];  

[$InputMedia](../types/InputMedia.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'q' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$InputMedia](../types/InputMedia.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$MessagesFilter](../types/MessagesFilter.md) = \[\];  

[$InputNotifyPeer](../types/InputNotifyPeer.md) = \[\];  

[$InputNotifyPeer](../types/InputNotifyPeer.md) = \[\];  

[$InputNotifyPeer](../types/InputNotifyPeer.md) = \['peer' => [InputPeer](../types/InputPeer.md), \];  

[$InputNotifyPeer](../types/InputNotifyPeer.md) = \[\];  

[$InputPeer](../types/InputPeer.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$InputPeer](../types/InputPeer.md) = \['chat_id' => [int](../types/int.md), \];  

[$InputPeer](../types/InputPeer.md) = \[\];  

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md) = \[\];  

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md) = \[\];  

[$InputPeerNotifySettings](../types/InputPeerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$InputPeer](../types/InputPeer.md) = \[\];  

[$InputPeer](../types/InputPeer.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$InputContact](../types/InputContact.md) = \['client_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];  

[$InputPhoto](../types/InputPhoto.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputPhoto](../types/InputPhoto.md) = \[\];  

[$InputPrivacyKey](../types/InputPrivacyKey.md) = \[\];  

[$InputPrivacyKey](../types/InputPrivacyKey.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];  

[$ReportReason](../types/ReportReason.md) = \['text' => [string](../types/string.md), \];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$InputStickerSet](../types/InputStickerSet.md) = \[\];  

[$InputStickerSet](../types/InputStickerSet.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$InputStickerSet](../types/InputStickerSet.md) = \['short_name' => [string](../types/string.md), \];  

[$InputStickeredMedia](../types/InputStickeredMedia.md) = \['id' => [InputDocument](../types/InputDocument.md), \];  

[$InputStickeredMedia](../types/InputStickeredMedia.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$InputUser](../types/InputUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$InputUser](../types/InputUser.md) = \[\];  

[$InputUser](../types/InputUser.md) = \[\];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), 'data' => [bytes](../types/bytes.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButtonRow](../types/KeyboardButtonRow.md) = \['buttons' => \[[KeyboardButton](../types/KeyboardButton.md)\], \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['same_peer' => [Bool](../types/Bool.md), 'text' => [string](../types/string.md), 'query' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$MaskCoords](../types/MaskCoords.md) = \['n' => [int](../types/int.md), 'x' => [double](../types/double.md), 'y' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \];  

[$Message](../types/Message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'views' => [int](../types/int.md), 'edit_date' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), 'chat_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['users' => \[[int](../types/int.md)\], \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), 'users' => \[[int](../types/int.md)\], \];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$MessageAction](../types/MessageAction.md) = \['user_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['photo' => [Photo](../types/Photo.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), \];  

[$MessageAction](../types/MessageAction.md) = \['inviter_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['channel_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$MessageAction](../types/MessageAction.md) = \['game_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$Message](../types/Message.md) = \['id' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageFwdHeader](../types/MessageFwdHeader.md) = \['from_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'channel_id' => [int](../types/int.md), 'channel_post' => [int](../types/int.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['document' => [Document](../types/Document.md), 'caption' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \[\];  

[$MessageMedia](../types/MessageMedia.md) = \['game' => [Game](../types/Game.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \[\];  

[$MessageMedia](../types/MessageMedia.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['webpage' => [WebPage](../types/WebPage.md), \];  

[$MessageRange](../types/MessageRange.md) = \['min_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$Message](../types/Message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \];  

[$messages\_AffectedHistory](../types/messages_AffectedHistory.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \];  

[$messages\_AffectedMessages](../types/messages_AffectedMessages.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$messages\_AllStickers](../types/messages_AllStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSet](../types/StickerSet.md)\], \];  

[$messages\_AllStickers](../types/messages_AllStickers.md) = \[\];  

[$messages\_ArchivedStickers](../types/messages_ArchivedStickers.md) = \['count' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];  

[$messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md) = \['alert' => [Bool](../types/Bool.md), 'has_url' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$messages\_BotResults](../types/messages_BotResults.md) = \['gallery' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'next_offset' => [string](../types/string.md), 'switch_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), 'results' => \[[BotInlineResult](../types/BotInlineResult.md)\], \];  

[$messages\_Messages](../types/messages_Messages.md) = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_ChatFull](../types/messages_ChatFull.md) = \['full_chat' => [ChatFull](../types/ChatFull.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_Chats](../types/messages_Chats.md) = \['chats' => \[[Chat](../types/Chat.md)\], \];  

[$messages\_DhConfig](../types/messages_DhConfig.md) = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \];  

[$messages\_DhConfig](../types/messages_DhConfig.md) = \['random' => [bytes](../types/bytes.md), \];  

[$messages\_Dialogs](../types/messages_Dialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_Dialogs](../types/messages_Dialogs.md) = \['count' => [int](../types/int.md), 'dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_FeaturedStickers](../types/messages_FeaturedStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], 'unread' => \[[long](../types/long.md)\], \];  

[$messages\_FeaturedStickers](../types/messages_FeaturedStickers.md) = \[\];  

[$messages\_FoundGifs](../types/messages_FoundGifs.md) = \['next_offset' => [int](../types/int.md), 'results' => \[[FoundGif](../types/FoundGif.md)\], \];  

[$messages\_HighScores](../types/messages_HighScores.md) = \['scores' => \[[HighScore](../types/HighScore.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_MessageEditData](../types/messages_MessageEditData.md) = \['caption' => [Bool](../types/Bool.md), \];  

[$messages\_Messages](../types/messages_Messages.md) = \['messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_Messages](../types/messages_Messages.md) = \['count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_PeerDialogs](../types/messages_PeerDialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];  

[$messages\_RecentStickers](../types/messages_RecentStickers.md) = \['hash' => [int](../types/int.md), 'stickers' => \[[Document](../types/Document.md)\], \];  

[$messages\_RecentStickers](../types/messages_RecentStickers.md) = \[\];  

[$messages\_SavedGifs](../types/messages_SavedGifs.md) = \['hash' => [int](../types/int.md), 'gifs' => \[[Document](../types/Document.md)\], \];  

[$messages\_SavedGifs](../types/messages_SavedGifs.md) = \[\];  

[$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md) = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md) = \['date' => [int](../types/int.md), \];  

[$messages\_StickerSet](../types/messages_StickerSet.md) = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => \[[StickerPack](../types/StickerPack.md)\], 'documents' => \[[Document](../types/Document.md)\], \];  

[$messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md) = \['sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];  

[$messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md) = \[\];  

[$messages\_Stickers](../types/messages_Stickers.md) = \['hash' => [string](../types/string.md), 'stickers' => \[[Document](../types/Document.md)\], \];  

[$messages\_Stickers](../types/messages_Stickers.md) = \[\];  

[$NearestDc](../types/NearestDc.md) = \['country' => [string](../types/string.md), 'this_dc' => [int](../types/int.md), 'nearest_dc' => [int](../types/int.md), \];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$NotifyPeer](../types/NotifyPeer.md) = \['peer' => [Peer](../types/Peer.md), \];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$Null](../types/Null.md) = \[\];  

[$Peer](../types/Peer.md) = \['channel_id' => [int](../types/int.md), \];  

[$Peer](../types/Peer.md) = \['chat_id' => [int](../types/int.md), \];  

[$PeerNotifyEvents](../types/PeerNotifyEvents.md) = \[\];  

[$PeerNotifyEvents](../types/PeerNotifyEvents.md) = \[\];  

[$PeerNotifySettings](../types/PeerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$PeerNotifySettings](../types/PeerNotifySettings.md) = \[\];  

[$PeerSettings](../types/PeerSettings.md) = \['report_spam' => [Bool](../types/Bool.md), \];  

[$Peer](../types/Peer.md) = \['user_id' => [int](../types/int.md), \];  

[$Photo](../types/Photo.md) = \['has_stickers' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$Photo](../types/Photo.md) = \['id' => [long](../types/long.md), \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'size' => [int](../types/int.md), \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), \];  

[$photos\_Photo](../types/photos_Photo.md) = \['photo' => [Photo](../types/Photo.md), 'users' => \[[User](../types/User.md)\], \];  

[$photos\_Photos](../types/photos_Photos.md) = \['photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$photos\_Photos](../types/photos_Photos.md) = \['count' => [int](../types/int.md), 'photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$PrivacyKey](../types/PrivacyKey.md) = \[\];  

[$PrivacyKey](../types/PrivacyKey.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \['users' => \[[int](../types/int.md)\], \];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \['users' => \[[int](../types/int.md)\], \];  

[$ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md) = \['id' => [int](../types/int.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['selective' => [Bool](../types/Bool.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['resize' => [Bool](../types/Bool.md), 'single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \[\];  

[$SendMessageAction](../types/SendMessageAction.md) = \['progress' => [int](../types/int.md), \];  

[$SendMessageAction](../types/SendMessageAction.md) = \['progress' => [int](../types/int.md), \];  

[$SendMessageAction](../types/SendMessageAction.md) = \['progress' => [int](../types/int.md), \];  

[$SendMessageAction](../types/SendMessageAction.md) = \['progress' => [int](../types/int.md), \];  

[$StickerPack](../types/StickerPack.md) = \['emoticon' => [string](../types/string.md), 'documents' => \[[long](../types/long.md)\], \];  

[$StickerSet](../types/StickerSet.md) = \['installed' => [Bool](../types/Bool.md), 'archived' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'masks' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \];  

[$StickerSetCovered](../types/StickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'cover' => [Document](../types/Document.md), \];  

[$StickerSetCovered](../types/StickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'covers' => \[[Document](../types/Document.md)\], \];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$storage\_FileType](../types/storage_FileType.md) = \[\];  

[$TopPeer](../types/TopPeer.md) = \['peer' => [Peer](../types/Peer.md), 'rating' => [double](../types/double.md), \];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md) = \['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'count' => [int](../types/int.md), 'peers' => \[[TopPeer](../types/TopPeer.md)\], \];  

[$True](../types/True.md) = \[\];  

[$Update](../types/Update.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'peer' => [Peer](../types/Peer.md), 'msg_id' => [int](../types/int.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'offset' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'id' => [string](../types/string.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'is_admin' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['participants' => [ChatParticipants](../types/ChatParticipants.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['dc_options' => \[[DcOption](../types/DcOption.md)\], \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat_id' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), \];  

[$Update](../types/Update.md) = \['auth_key_id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'device' => [string](../types/string.md), 'location' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['stickerset' => [messages\_StickerSet](../types/messages_StickerSet.md), \];  

[$Update](../types/Update.md) = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];  

[$Update](../types/Update.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'popup' => [Bool](../types/Bool.md), \];  

[$Updates](../types/Updates.md) = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'chat_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['masks' => [Bool](../types/Bool.md), 'order' => \[[long](../types/long.md)\], \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'blocked' => [Bool](../types/Bool.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'phone' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'previous' => [Bool](../types/Bool.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$Update](../types/Update.md) = \['user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$Update](../types/Update.md) = \['webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq_start' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \[\];  

[$updates\_ChannelDifference](../types/updates_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'new_messages' => \[[Message](../types/Message.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$updates\_ChannelDifference](../types/updates_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), \];  

[$updates\_ChannelDifference](../types/updates_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$updates\_Difference](../types/updates_Difference.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];  

[$updates\_Difference](../types/updates_Difference.md) = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$updates\_Difference](../types/updates_Difference.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'intermediate_state' => [updates\_State](../types/updates_State.md), \];  

[$updates\_State](../types/updates_State.md) = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), \];  

[$upload\_File](../types/upload_File.md) = \['type' => [storage\_FileType](../types/storage_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$User](../types/User.md) = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot_chat_history' => [Bool](../types/Bool.md), 'bot_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'bot_inline_geo' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot_info_version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), 'bot_inline_placeholder' => [string](../types/string.md), \];  

[$User](../types/User.md) = \['id' => [int](../types/int.md), \];  

[$UserFull](../types/UserFull.md) = \['blocked' => [Bool](../types/Bool.md), 'user' => [User](../types/User.md), 'about' => [string](../types/string.md), 'link' => [contacts\_Link](../types/contacts_Link.md), 'profile_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'bot_info' => [BotInfo](../types/BotInfo.md), \];  

[$UserProfilePhoto](../types/UserProfilePhoto.md) = \['photo_id' => [long](../types/long.md), 'photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];  

[$UserProfilePhoto](../types/UserProfilePhoto.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \['was_online' => [int](../types/int.md), \];  

[$UserStatus](../types/UserStatus.md) = \['expires' => [int](../types/int.md), \];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$Vector t](../types/Vector t.md) = \[\];  

[$WallPaper](../types/WallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], 'color' => [int](../types/int.md), \];  

[$WallPaper](../types/WallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \];  

