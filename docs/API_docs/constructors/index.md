# Constructors  

[$accountDaysTTL](../constructors/accountDaysTTL.md) = \['days' => [int](../types/int.md), \];  

[$account\_authorizations](../constructors/account_authorizations.md) = \['authorizations' => \[[Authorization](../types/Authorization.md)\], \];  

[$account\_noPassword](../constructors/account_noPassword.md) = \['new_salt' => [bytes](../types/bytes.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];  

[$account\_password](../constructors/account_password.md) = \['current_salt' => [bytes](../types/bytes.md), 'new_salt' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'has_recovery' => [Bool](../types/Bool.md), 'email_unconfirmed_pattern' => [string](../types/string.md), \];  

[$account\_passwordInputSettings](../constructors/account_passwordInputSettings.md) = \['new_salt' => [bytes](../types/bytes.md), 'new_password_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'email' => [string](../types/string.md), \];  

[$account\_passwordSettings](../constructors/account_passwordSettings.md) = \['email' => [string](../types/string.md), \];  

[$account\_privacyRules](../constructors/account_privacyRules.md) = \['rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$auth\_authorization](../constructors/auth_authorization.md) = \['tmp_sessions' => [int](../types/int.md), 'user' => [User](../types/User.md), \];  

[$auth\_checkedPhone](../constructors/auth_checkedPhone.md) = \['phone_registered' => [Bool](../types/Bool.md), \];  

[$auth\_codeTypeCall](../constructors/auth_codeTypeCall.md) = \[\];  

[$auth\_codeTypeFlashCall](../constructors/auth_codeTypeFlashCall.md) = \[\];  

[$auth\_codeTypeSms](../constructors/auth_codeTypeSms.md) = \[\];  

[$auth\_exportedAuthorization](../constructors/auth_exportedAuthorization.md) = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$auth\_passwordRecovery](../constructors/auth_passwordRecovery.md) = \['email_pattern' => [string](../types/string.md), \];  

[$auth\_sentCode](../constructors/auth_sentCode.md) = \['phone_registered' => [Bool](../types/Bool.md), 'type' => [auth\_SentCodeType](../types/auth_SentCodeType.md), 'phone_code_hash' => [string](../types/string.md), 'next_type' => [auth\_CodeType](../types/auth_CodeType.md), 'timeout' => [int](../types/int.md), \];  

[$auth\_sentCodeTypeApp](../constructors/auth_sentCodeTypeApp.md) = \['length' => [int](../types/int.md), \];  

[$auth\_sentCodeTypeCall](../constructors/auth_sentCodeTypeCall.md) = \['length' => [int](../types/int.md), \];  

[$auth\_sentCodeTypeFlashCall](../constructors/auth_sentCodeTypeFlashCall.md) = \['pattern' => [string](../types/string.md), \];  

[$auth\_sentCodeTypeSms](../constructors/auth_sentCodeTypeSms.md) = \['length' => [int](../types/int.md), \];  

[$authorization](../constructors/authorization.md) = \['hash' => [long](../types/long.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'api_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'date_created' => [int](../types/int.md), 'date_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];  

[$boolFalse](../constructors/boolFalse.md) = \[\];  

[$boolTrue](../constructors/boolTrue.md) = \[\];  

[$botCommand](../constructors/botCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$botInfo](../constructors/botInfo.md) = \['user_id' => [int](../types/int.md), 'description' => [string](../types/string.md), 'commands' => \[[BotCommand](../types/BotCommand.md)\], \];  

[$botInlineMediaResult](../constructors/botInlineMediaResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$botInlineMessageMediaAuto](../constructors/botInlineMessageMediaAuto.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$botInlineMessageMediaContact](../constructors/botInlineMessageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$botInlineMessageMediaGeo](../constructors/botInlineMessageMediaGeo.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$botInlineMessageMediaVenue](../constructors/botInlineMessageMediaVenue.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$botInlineMessageText](../constructors/botInlineMessageText.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$botInlineResult](../constructors/botInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$channel](../constructors/channel.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'editor' => [Bool](../types/Bool.md), 'moderator' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'democracy' => [Bool](../types/Bool.md), 'signatures' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), \];  

[$channelForbidden](../constructors/channelForbidden.md) = \['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), \];  

[$channelFull](../constructors/channelFull.md) = \['can_view_participants' => [Bool](../types/Bool.md), 'can_set_username' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants_count' => [int](../types/int.md), 'admins_count' => [int](../types/int.md), 'kicked_count' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], 'migrated_from_chat_id' => [int](../types/int.md), 'migrated_from_max_id' => [int](../types/int.md), 'pinned_msg_id' => [int](../types/int.md), \];  

[$channelMessagesFilter](../constructors/channelMessagesFilter.md) = \['exclude_new_messages' => [Bool](../types/Bool.md), 'ranges' => \[[MessageRange](../types/MessageRange.md)\], \];  

[$channelMessagesFilterEmpty](../constructors/channelMessagesFilterEmpty.md) = \[\];  

[$channelParticipant](../constructors/channelParticipant.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$channelParticipantCreator](../constructors/channelParticipantCreator.md) = \['user_id' => [int](../types/int.md), \];  

[$channelParticipantEditor](../constructors/channelParticipantEditor.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$channelParticipantKicked](../constructors/channelParticipantKicked.md) = \['user_id' => [int](../types/int.md), 'kicked_by' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$channelParticipantModerator](../constructors/channelParticipantModerator.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$channelParticipantSelf](../constructors/channelParticipantSelf.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$channelParticipantsAdmins](../constructors/channelParticipantsAdmins.md) = \[\];  

[$channelParticipantsBots](../constructors/channelParticipantsBots.md) = \[\];  

[$channelParticipantsKicked](../constructors/channelParticipantsKicked.md) = \[\];  

[$channelParticipantsRecent](../constructors/channelParticipantsRecent.md) = \[\];  

[$channelRoleEditor](../constructors/channelRoleEditor.md) = \[\];  

[$channelRoleEmpty](../constructors/channelRoleEmpty.md) = \[\];  

[$channelRoleModerator](../constructors/channelRoleModerator.md) = \[\];  

[$channels\_channelParticipant](../constructors/channels_channelParticipant.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => \[[User](../types/User.md)\], \];  

[$channels\_channelParticipants](../constructors/channels_channelParticipants.md) = \['count' => [int](../types/int.md), 'participants' => \[[ChannelParticipant](../types/ChannelParticipant.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$chat](../constructors/chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'admins_enabled' => [Bool](../types/Bool.md), 'admin' => [Bool](../types/Bool.md), 'deactivated' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'migrated_to' => [InputChannel](../types/InputChannel.md), \];  

[$chatEmpty](../constructors/chatEmpty.md) = \['id' => [int](../types/int.md), \];  

[$chatForbidden](../constructors/chatForbidden.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), \];  

[$chatFull](../constructors/chatFull.md) = \['id' => [int](../types/int.md), 'participants' => [ChatParticipants](../types/ChatParticipants.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], \];  

[$chatInvite](../constructors/chatInvite.md) = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants_count' => [int](../types/int.md), 'participants' => \[[User](../types/User.md)\], \];  

[$chatInviteAlready](../constructors/chatInviteAlready.md) = \['chat' => [Chat](../types/Chat.md), \];  

[$chatInviteEmpty](../constructors/chatInviteEmpty.md) = \[\];  

[$chatInviteExported](../constructors/chatInviteExported.md) = \['link' => [string](../types/string.md), \];  

[$chatParticipant](../constructors/chatParticipant.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$chatParticipantAdmin](../constructors/chatParticipantAdmin.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$chatParticipantCreator](../constructors/chatParticipantCreator.md) = \['user_id' => [int](../types/int.md), \];  

[$chatParticipants](../constructors/chatParticipants.md) = \['chat_id' => [int](../types/int.md), 'participants' => \[[ChatParticipant](../types/ChatParticipant.md)\], 'version' => [int](../types/int.md), \];  

[$chatParticipantsForbidden](../constructors/chatParticipantsForbidden.md) = \['chat_id' => [int](../types/int.md), 'self_participant' => [ChatParticipant](../types/ChatParticipant.md), \];  

[$chatPhoto](../constructors/chatPhoto.md) = \['photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];  

[$chatPhotoEmpty](../constructors/chatPhotoEmpty.md) = \[\];  

[$config](../constructors/config.md) = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test_mode' => [Bool](../types/Bool.md), 'this_dc' => [int](../types/int.md), 'dc_options' => \[[DcOption](../types/DcOption.md)\], 'chat_size_max' => [int](../types/int.md), 'megagroup_size_max' => [int](../types/int.md), 'forwarded_count_max' => [int](../types/int.md), 'online_update_period_ms' => [int](../types/int.md), 'offline_blur_timeout_ms' => [int](../types/int.md), 'offline_idle_timeout_ms' => [int](../types/int.md), 'online_cloud_timeout_ms' => [int](../types/int.md), 'notify_cloud_delay_ms' => [int](../types/int.md), 'notify_default_delay_ms' => [int](../types/int.md), 'chat_big_size' => [int](../types/int.md), 'push_chat_period_ms' => [int](../types/int.md), 'push_chat_limit' => [int](../types/int.md), 'saved_gifs_limit' => [int](../types/int.md), 'edit_time_limit' => [int](../types/int.md), 'rating_e_decay' => [int](../types/int.md), 'stickers_recent_limit' => [int](../types/int.md), 'tmp_sessions' => [int](../types/int.md), 'disabled_features' => \[[DisabledFeature](../types/DisabledFeature.md)\], \];  

[$contact](../constructors/contact.md) = \['user_id' => [int](../types/int.md), 'mutual' => [Bool](../types/Bool.md), \];  

[$contactBlocked](../constructors/contactBlocked.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$contactLinkContact](../constructors/contactLinkContact.md) = \[\];  

[$contactLinkHasPhone](../constructors/contactLinkHasPhone.md) = \[\];  

[$contactLinkNone](../constructors/contactLinkNone.md) = \[\];  

[$contactLinkUnknown](../constructors/contactLinkUnknown.md) = \[\];  

[$contactStatus](../constructors/contactStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$contacts\_blocked](../constructors/contacts_blocked.md) = \['blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_blockedSlice](../constructors/contacts_blockedSlice.md) = \['count' => [int](../types/int.md), 'blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_contacts](../constructors/contacts_contacts.md) = \['contacts' => \[[Contact](../types/Contact.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_contactsNotModified](../constructors/contacts_contactsNotModified.md) = \[\];  

[$contacts\_found](../constructors/contacts_found.md) = \['results' => \[[Peer](../types/Peer.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_importedContacts](../constructors/contacts_importedContacts.md) = \['imported' => \[[ImportedContact](../types/ImportedContact.md)\], 'retry_contacts' => \[[long](../types/long.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_link](../constructors/contacts_link.md) = \['my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \];  

[$contacts\_resolvedPeer](../constructors/contacts_resolvedPeer.md) = \['peer' => [Peer](../types/Peer.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_topPeers](../constructors/contacts_topPeers.md) = \['categories' => \[[TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$contacts\_topPeersNotModified](../constructors/contacts_topPeersNotModified.md) = \[\];  

[$dcOption](../constructors/dcOption.md) = \['ipv6' => [Bool](../types/Bool.md), 'media_only' => [Bool](../types/Bool.md), 'tcpo_only' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \];  

[$dialog](../constructors/dialog.md) = \['peer' => [Peer](../types/Peer.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$disabledFeature](../constructors/disabledFeature.md) = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$document](../constructors/document.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];  

[$documentAttributeAnimated](../constructors/documentAttributeAnimated.md) = \[\];  

[$documentAttributeAudio](../constructors/documentAttributeAudio.md) = \['voice' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'waveform' => [bytes](../types/bytes.md), \];  

[$documentAttributeFilename](../constructors/documentAttributeFilename.md) = \['file_name' => [string](../types/string.md), \];  

[$documentAttributeHasStickers](../constructors/documentAttributeHasStickers.md) = \[\];  

[$documentAttributeImageSize](../constructors/documentAttributeImageSize.md) = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$documentAttributeSticker](../constructors/documentAttributeSticker.md) = \['mask' => [Bool](../types/Bool.md), 'alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'mask_coords' => [MaskCoords](../types/MaskCoords.md), \];  

[$documentAttributeVideo](../constructors/documentAttributeVideo.md) = \['duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$documentEmpty](../constructors/documentEmpty.md) = \['id' => [long](../types/long.md), \];  

[$draftMessage](../constructors/draftMessage.md) = \['no_webpage' => [Bool](../types/Bool.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'date' => [int](../types/int.md), \];  

[$draftMessageEmpty](../constructors/draftMessageEmpty.md) = \[\];  

[$encryptedChat](../constructors/encryptedChat.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a_or_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \];  

[$encryptedChatDiscarded](../constructors/encryptedChatDiscarded.md) = \['id' => [int](../types/int.md), \];  

[$encryptedChatEmpty](../constructors/encryptedChatEmpty.md) = \['id' => [int](../types/int.md), \];  

[$encryptedChatRequested](../constructors/encryptedChatRequested.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), 'g_a' => [bytes](../types/bytes.md), \];  

[$encryptedChatWaiting](../constructors/encryptedChatWaiting.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin_id' => [int](../types/int.md), 'participant_id' => [int](../types/int.md), \];  

[$encryptedFile](../constructors/encryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'dc_id' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$encryptedFileEmpty](../constructors/encryptedFileEmpty.md) = \[\];  

[$encryptedMessage](../constructors/encryptedMessage.md) = \['random_id' => [long](../types/long.md), 'chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$encryptedMessageService](../constructors/encryptedMessageService.md) = \['random_id' => [long](../types/long.md), 'chat_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$error](../constructors/error.md) = \['code' => [int](../types/int.md), 'text' => [string](../types/string.md), \];  

[$exportedMessageLink](../constructors/exportedMessageLink.md) = \['link' => [string](../types/string.md), \];  

[$fileLocation](../constructors/fileLocation.md) = \['dc_id' => [int](../types/int.md), 'volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$fileLocationUnavailable](../constructors/fileLocationUnavailable.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$foundGif](../constructors/foundGif.md) = \['url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$foundGifCached](../constructors/foundGifCached.md) = \['url' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$game](../constructors/game.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'short_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$geoPoint](../constructors/geoPoint.md) = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \];  

[$geoPointEmpty](../constructors/geoPointEmpty.md) = \[\];  

[$help\_appChangelog](../constructors/help_appChangelog.md) = \['text' => [string](../types/string.md), \];  

[$help\_appChangelogEmpty](../constructors/help_appChangelogEmpty.md) = \[\];  

[$help\_appUpdate](../constructors/help_appUpdate.md) = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \];  

[$help\_inviteText](../constructors/help_inviteText.md) = \['message' => [string](../types/string.md), \];  

[$help\_noAppUpdate](../constructors/help_noAppUpdate.md) = \[\];  

[$help\_support](../constructors/help_support.md) = \['phone_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \];  

[$help\_termsOfService](../constructors/help_termsOfService.md) = \['text' => [string](../types/string.md), \];  

[$highScore](../constructors/highScore.md) = \['pos' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];  

[$importedContact](../constructors/importedContact.md) = \['user_id' => [int](../types/int.md), 'client_id' => [long](../types/long.md), \];  

[$inlineBotSwitchPM](../constructors/inlineBotSwitchPM.md) = \['text' => [string](../types/string.md), 'start_param' => [string](../types/string.md), \];  

[$inputAppEvent](../constructors/inputAppEvent.md) = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \];  

[$inputBotInlineMessageGame](../constructors/inputBotInlineMessageGame.md) = \['reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineMessageID](../constructors/inputBotInlineMessageID.md) = \['dc_id' => [int](../types/int.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputBotInlineMessageMediaAuto](../constructors/inputBotInlineMessageMediaAuto.md) = \['caption' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineMessageMediaContact](../constructors/inputBotInlineMessageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineMessageMediaGeo](../constructors/inputBotInlineMessageMediaGeo.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineMessageMediaVenue](../constructors/inputBotInlineMessageMediaVenue.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineMessageText](../constructors/inputBotInlineMessageText.md) = \['no_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$inputBotInlineResult](../constructors/inputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'content_url' => [string](../types/string.md), 'content_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$inputBotInlineResultDocument](../constructors/inputBotInlineResultDocument.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document' => [InputDocument](../types/InputDocument.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$inputBotInlineResultGame](../constructors/inputBotInlineResultGame.md) = \['id' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$inputBotInlineResultPhoto](../constructors/inputBotInlineResultPhoto.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [InputPhoto](../types/InputPhoto.md), 'send_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$inputChannel](../constructors/inputChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$inputChannelEmpty](../constructors/inputChannelEmpty.md) = \[\];  

[$inputChatPhoto](../constructors/inputChatPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$inputChatPhotoEmpty](../constructors/inputChatPhotoEmpty.md) = \[\];  

[$inputChatUploadedPhoto](../constructors/inputChatUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), \];  

[$inputDocument](../constructors/inputDocument.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputDocumentEmpty](../constructors/inputDocumentEmpty.md) = \[\];  

[$inputDocumentFileLocation](../constructors/inputDocumentFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'version' => [int](../types/int.md), \];  

[$inputEncryptedChat](../constructors/inputEncryptedChat.md) = \['chat_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$inputEncryptedFile](../constructors/inputEncryptedFile.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputEncryptedFileBigUploaded](../constructors/inputEncryptedFileBigUploaded.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$inputEncryptedFileEmpty](../constructors/inputEncryptedFileEmpty.md) = \[\];  

[$inputEncryptedFileLocation](../constructors/inputEncryptedFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputEncryptedFileUploaded](../constructors/inputEncryptedFileUploaded.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'md5_checksum' => [string](../types/string.md), 'key_fingerprint' => [int](../types/int.md), \];  

[$inputFile](../constructors/inputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), 'md5_checksum' => [string](../types/string.md), \];  

[$inputFileBig](../constructors/inputFileBig.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), \];  

[$inputFileLocation](../constructors/inputFileLocation.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$inputGameID](../constructors/inputGameID.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputGameShortName](../constructors/inputGameShortName.md) = \['bot_id' => [InputUser](../types/InputUser.md), 'short_name' => [string](../types/string.md), \];  

[$inputGeoPoint](../constructors/inputGeoPoint.md) = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \];  

[$inputGeoPointEmpty](../constructors/inputGeoPointEmpty.md) = \[\];  

[$inputMediaContact](../constructors/inputMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];  

[$inputMediaDocument](../constructors/inputMediaDocument.md) = \['id' => [InputDocument](../types/InputDocument.md), 'caption' => [string](../types/string.md), \];  

[$inputMediaDocumentExternal](../constructors/inputMediaDocumentExternal.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$inputMediaEmpty](../constructors/inputMediaEmpty.md) = \[\];  

[$inputMediaGame](../constructors/inputMediaGame.md) = \['id' => [InputGame](../types/InputGame.md), \];  

[$inputMediaGeoPoint](../constructors/inputMediaGeoPoint.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), \];  

[$inputMediaGifExternal](../constructors/inputMediaGifExternal.md) = \['url' => [string](../types/string.md), 'q' => [string](../types/string.md), \];  

[$inputMediaPhoto](../constructors/inputMediaPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \];  

[$inputMediaPhotoExternal](../constructors/inputMediaPhotoExternal.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$inputMediaUploadedDocument](../constructors/inputMediaUploadedDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$inputMediaUploadedPhoto](../constructors/inputMediaUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$inputMediaUploadedThumbDocument](../constructors/inputMediaUploadedThumbDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], 'caption' => [string](../types/string.md), 'stickers' => \[[InputDocument](../types/InputDocument.md)\], \];  

[$inputMediaVenue](../constructors/inputMediaVenue.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];  

[$inputMessageEntityMentionName](../constructors/inputMessageEntityMentionName.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \];  

[$inputMessagesFilterChatPhotos](../constructors/inputMessagesFilterChatPhotos.md) = \[\];  

[$inputMessagesFilterDocument](../constructors/inputMessagesFilterDocument.md) = \[\];  

[$inputMessagesFilterEmpty](../constructors/inputMessagesFilterEmpty.md) = \[\];  

[$inputMessagesFilterGif](../constructors/inputMessagesFilterGif.md) = \[\];  

[$inputMessagesFilterMusic](../constructors/inputMessagesFilterMusic.md) = \[\];  

[$inputMessagesFilterPhotoVideo](../constructors/inputMessagesFilterPhotoVideo.md) = \[\];  

[$inputMessagesFilterPhotoVideoDocuments](../constructors/inputMessagesFilterPhotoVideoDocuments.md) = \[\];  

[$inputMessagesFilterPhotos](../constructors/inputMessagesFilterPhotos.md) = \[\];  

[$inputMessagesFilterUrl](../constructors/inputMessagesFilterUrl.md) = \[\];  

[$inputMessagesFilterVideo](../constructors/inputMessagesFilterVideo.md) = \[\];  

[$inputMessagesFilterVoice](../constructors/inputMessagesFilterVoice.md) = \[\];  

[$inputNotifyAll](../constructors/inputNotifyAll.md) = \[\];  

[$inputNotifyChats](../constructors/inputNotifyChats.md) = \[\];  

[$inputNotifyPeer](../constructors/inputNotifyPeer.md) = \['peer' => [InputPeer](../types/InputPeer.md), \];  

[$inputNotifyUsers](../constructors/inputNotifyUsers.md) = \[\];  

[$inputPeerChannel](../constructors/inputPeerChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$inputPeerChat](../constructors/inputPeerChat.md) = \['chat_id' => [int](../types/int.md), \];  

[$inputPeerEmpty](../constructors/inputPeerEmpty.md) = \[\];  

[$inputPeerNotifyEventsAll](../constructors/inputPeerNotifyEventsAll.md) = \[\];  

[$inputPeerNotifyEventsEmpty](../constructors/inputPeerNotifyEventsEmpty.md) = \[\];  

[$inputPeerNotifySettings](../constructors/inputPeerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$inputPeerSelf](../constructors/inputPeerSelf.md) = \[\];  

[$inputPeerUser](../constructors/inputPeerUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$inputPhoneContact](../constructors/inputPhoneContact.md) = \['client_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];  

[$inputPhoto](../constructors/inputPhoto.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputPhotoEmpty](../constructors/inputPhotoEmpty.md) = \[\];  

[$inputPrivacyKeyChatInvite](../constructors/inputPrivacyKeyChatInvite.md) = \[\];  

[$inputPrivacyKeyStatusTimestamp](../constructors/inputPrivacyKeyStatusTimestamp.md) = \[\];  

[$inputPrivacyValueAllowAll](../constructors/inputPrivacyValueAllowAll.md) = \[\];  

[$inputPrivacyValueAllowContacts](../constructors/inputPrivacyValueAllowContacts.md) = \[\];  

[$inputPrivacyValueAllowUsers](../constructors/inputPrivacyValueAllowUsers.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];  

[$inputPrivacyValueDisallowAll](../constructors/inputPrivacyValueDisallowAll.md) = \[\];  

[$inputPrivacyValueDisallowContacts](../constructors/inputPrivacyValueDisallowContacts.md) = \[\];  

[$inputPrivacyValueDisallowUsers](../constructors/inputPrivacyValueDisallowUsers.md) = \['users' => \[[InputUser](../types/InputUser.md)\], \];  

[$inputReportReasonOther](../constructors/inputReportReasonOther.md) = \['text' => [string](../types/string.md), \];  

[$inputReportReasonPornography](../constructors/inputReportReasonPornography.md) = \[\];  

[$inputReportReasonSpam](../constructors/inputReportReasonSpam.md) = \[\];  

[$inputReportReasonViolence](../constructors/inputReportReasonViolence.md) = \[\];  

[$inputStickerSetEmpty](../constructors/inputStickerSetEmpty.md) = \[\];  

[$inputStickerSetID](../constructors/inputStickerSetID.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];  

[$inputStickerSetShortName](../constructors/inputStickerSetShortName.md) = \['short_name' => [string](../types/string.md), \];  

[$inputStickeredMediaDocument](../constructors/inputStickeredMediaDocument.md) = \['id' => [InputDocument](../types/InputDocument.md), \];  

[$inputStickeredMediaPhoto](../constructors/inputStickeredMediaPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$inputUser](../constructors/inputUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];  

[$inputUserEmpty](../constructors/inputUserEmpty.md) = \[\];  

[$inputUserSelf](../constructors/inputUserSelf.md) = \[\];  

[$keyboardButton](../constructors/keyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$keyboardButtonCallback](../constructors/keyboardButtonCallback.md) = \['text' => [string](../types/string.md), 'data' => [bytes](../types/bytes.md), \];  

[$keyboardButtonGame](../constructors/keyboardButtonGame.md) = \['text' => [string](../types/string.md), \];  

[$keyboardButtonRequestGeoLocation](../constructors/keyboardButtonRequestGeoLocation.md) = \['text' => [string](../types/string.md), \];  

[$keyboardButtonRequestPhone](../constructors/keyboardButtonRequestPhone.md) = \['text' => [string](../types/string.md), \];  

[$keyboardButtonRow](../constructors/keyboardButtonRow.md) = \['buttons' => \[[KeyboardButton](../types/KeyboardButton.md)\], \];  

[$keyboardButtonSwitchInline](../constructors/keyboardButtonSwitchInline.md) = \['same_peer' => [Bool](../types/Bool.md), 'text' => [string](../types/string.md), 'query' => [string](../types/string.md), \];  

[$keyboardButtonUrl](../constructors/keyboardButtonUrl.md) = \['text' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$maskCoords](../constructors/maskCoords.md) = \['n' => [int](../types/int.md), 'x' => [double](../types/double.md), 'y' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \];  

[$message](../constructors/message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'views' => [int](../types/int.md), 'edit_date' => [int](../types/int.md), \];  

[$messageActionChannelCreate](../constructors/messageActionChannelCreate.md) = \['title' => [string](../types/string.md), \];  

[$messageActionChannelMigrateFrom](../constructors/messageActionChannelMigrateFrom.md) = \['title' => [string](../types/string.md), 'chat_id' => [int](../types/int.md), \];  

[$messageActionChatAddUser](../constructors/messageActionChatAddUser.md) = \['users' => \[[int](../types/int.md)\], \];  

[$messageActionChatCreate](../constructors/messageActionChatCreate.md) = \['title' => [string](../types/string.md), 'users' => \[[int](../types/int.md)\], \];  

[$messageActionChatDeletePhoto](../constructors/messageActionChatDeletePhoto.md) = \[\];  

[$messageActionChatDeleteUser](../constructors/messageActionChatDeleteUser.md) = \['user_id' => [int](../types/int.md), \];  

[$messageActionChatEditPhoto](../constructors/messageActionChatEditPhoto.md) = \['photo' => [Photo](../types/Photo.md), \];  

[$messageActionChatEditTitle](../constructors/messageActionChatEditTitle.md) = \['title' => [string](../types/string.md), \];  

[$messageActionChatJoinedByLink](../constructors/messageActionChatJoinedByLink.md) = \['inviter_id' => [int](../types/int.md), \];  

[$messageActionChatMigrateTo](../constructors/messageActionChatMigrateTo.md) = \['channel_id' => [int](../types/int.md), \];  

[$messageActionEmpty](../constructors/messageActionEmpty.md) = \[\];  

[$messageActionGameScore](../constructors/messageActionGameScore.md) = \['game_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];  

[$messageActionHistoryClear](../constructors/messageActionHistoryClear.md) = \[\];  

[$messageActionPinMessage](../constructors/messageActionPinMessage.md) = \[\];  

[$messageEmpty](../constructors/messageEmpty.md) = \['id' => [int](../types/int.md), \];  

[$messageEntityBold](../constructors/messageEntityBold.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityBotCommand](../constructors/messageEntityBotCommand.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityCode](../constructors/messageEntityCode.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityEmail](../constructors/messageEntityEmail.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityHashtag](../constructors/messageEntityHashtag.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityItalic](../constructors/messageEntityItalic.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityMention](../constructors/messageEntityMention.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityMentionName](../constructors/messageEntityMentionName.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user_id' => [int](../types/int.md), \];  

[$messageEntityPre](../constructors/messageEntityPre.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];  

[$messageEntityTextUrl](../constructors/messageEntityTextUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];  

[$messageEntityUnknown](../constructors/messageEntityUnknown.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageEntityUrl](../constructors/messageEntityUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$messageFwdHeader](../constructors/messageFwdHeader.md) = \['from_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'channel_id' => [int](../types/int.md), 'channel_post' => [int](../types/int.md), \];  

[$messageMediaContact](../constructors/messageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];  

[$messageMediaDocument](../constructors/messageMediaDocument.md) = \['document' => [Document](../types/Document.md), 'caption' => [string](../types/string.md), \];  

[$messageMediaEmpty](../constructors/messageMediaEmpty.md) = \[\];  

[$messageMediaGame](../constructors/messageMediaGame.md) = \['game' => [Game](../types/Game.md), \];  

[$messageMediaGeo](../constructors/messageMediaGeo.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), \];  

[$messageMediaPhoto](../constructors/messageMediaPhoto.md) = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \];  

[$messageMediaUnsupported](../constructors/messageMediaUnsupported.md) = \[\];  

[$messageMediaVenue](../constructors/messageMediaVenue.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];  

[$messageMediaWebPage](../constructors/messageMediaWebPage.md) = \['webpage' => [WebPage](../types/WebPage.md), \];  

[$messageRange](../constructors/messageRange.md) = \['min_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$messageService](../constructors/messageService.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \];  

[$messages\_affectedHistory](../constructors/messages_affectedHistory.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \];  

[$messages\_affectedMessages](../constructors/messages_affectedMessages.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$messages\_allStickers](../constructors/messages_allStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSet](../types/StickerSet.md)\], \];  

[$messages\_allStickersNotModified](../constructors/messages_allStickersNotModified.md) = \[\];  

[$messages\_archivedStickers](../constructors/messages_archivedStickers.md) = \['count' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];  

[$messages\_botCallbackAnswer](../constructors/messages_botCallbackAnswer.md) = \['alert' => [Bool](../types/Bool.md), 'has_url' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$messages\_botResults](../constructors/messages_botResults.md) = \['gallery' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'next_offset' => [string](../types/string.md), 'switch_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), 'results' => \[[BotInlineResult](../types/BotInlineResult.md)\], \];  

[$messages\_channelMessages](../constructors/messages_channelMessages.md) = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_chatFull](../constructors/messages_chatFull.md) = \['full_chat' => [ChatFull](../types/ChatFull.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_chats](../constructors/messages_chats.md) = \['chats' => \[[Chat](../types/Chat.md)\], \];  

[$messages\_dhConfig](../constructors/messages_dhConfig.md) = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \];  

[$messages\_dhConfigNotModified](../constructors/messages_dhConfigNotModified.md) = \['random' => [bytes](../types/bytes.md), \];  

[$messages\_dialogs](../constructors/messages_dialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_dialogsSlice](../constructors/messages_dialogsSlice.md) = \['count' => [int](../types/int.md), 'dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_featuredStickers](../constructors/messages_featuredStickers.md) = \['hash' => [int](../types/int.md), 'sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], 'unread' => \[[long](../types/long.md)\], \];  

[$messages\_featuredStickersNotModified](../constructors/messages_featuredStickersNotModified.md) = \[\];  

[$messages\_foundGifs](../constructors/messages_foundGifs.md) = \['next_offset' => [int](../types/int.md), 'results' => \[[FoundGif](../types/FoundGif.md)\], \];  

[$messages\_highScores](../constructors/messages_highScores.md) = \['scores' => \[[HighScore](../types/HighScore.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_messageEditData](../constructors/messages_messageEditData.md) = \['caption' => [Bool](../types/Bool.md), \];  

[$messages\_messages](../constructors/messages_messages.md) = \['messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_messagesSlice](../constructors/messages_messagesSlice.md) = \['count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$messages\_peerDialogs](../constructors/messages_peerDialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];  

[$messages\_recentStickers](../constructors/messages_recentStickers.md) = \['hash' => [int](../types/int.md), 'stickers' => \[[Document](../types/Document.md)\], \];  

[$messages\_recentStickersNotModified](../constructors/messages_recentStickersNotModified.md) = \[\];  

[$messages\_savedGifs](../constructors/messages_savedGifs.md) = \['hash' => [int](../types/int.md), 'gifs' => \[[Document](../types/Document.md)\], \];  

[$messages\_savedGifsNotModified](../constructors/messages_savedGifsNotModified.md) = \[\];  

[$messages\_sentEncryptedFile](../constructors/messages_sentEncryptedFile.md) = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$messages\_sentEncryptedMessage](../constructors/messages_sentEncryptedMessage.md) = \['date' => [int](../types/int.md), \];  

[$messages\_stickerSet](../constructors/messages_stickerSet.md) = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => \[[StickerPack](../types/StickerPack.md)\], 'documents' => \[[Document](../types/Document.md)\], \];  

[$messages\_stickerSetInstallResultArchive](../constructors/messages_stickerSetInstallResultArchive.md) = \['sets' => \[[StickerSetCovered](../types/StickerSetCovered.md)\], \];  

[$messages\_stickerSetInstallResultSuccess](../constructors/messages_stickerSetInstallResultSuccess.md) = \[\];  

[$messages\_stickers](../constructors/messages_stickers.md) = \['hash' => [string](../types/string.md), 'stickers' => \[[Document](../types/Document.md)\], \];  

[$messages\_stickersNotModified](../constructors/messages_stickersNotModified.md) = \[\];  

[$nearestDc](../constructors/nearestDc.md) = \['country' => [string](../types/string.md), 'this_dc' => [int](../types/int.md), 'nearest_dc' => [int](../types/int.md), \];  

[$notifyAll](../constructors/notifyAll.md) = \[\];  

[$notifyChats](../constructors/notifyChats.md) = \[\];  

[$notifyPeer](../constructors/notifyPeer.md) = \['peer' => [Peer](../types/Peer.md), \];  

[$notifyUsers](../constructors/notifyUsers.md) = \[\];  

[$null](../constructors/null.md) = \[\];  

[$peerChannel](../constructors/peerChannel.md) = \['channel_id' => [int](../types/int.md), \];  

[$peerChat](../constructors/peerChat.md) = \['chat_id' => [int](../types/int.md), \];  

[$peerNotifyEventsAll](../constructors/peerNotifyEventsAll.md) = \[\];  

[$peerNotifyEventsEmpty](../constructors/peerNotifyEventsEmpty.md) = \[\];  

[$peerNotifySettings](../constructors/peerNotifySettings.md) = \['show_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$peerNotifySettingsEmpty](../constructors/peerNotifySettingsEmpty.md) = \[\];  

[$peerSettings](../constructors/peerSettings.md) = \['report_spam' => [Bool](../types/Bool.md), \];  

[$peerUser](../constructors/peerUser.md) = \['user_id' => [int](../types/int.md), \];  

[$photo](../constructors/photo.md) = \['has_stickers' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], \];  

[$photoCachedSize](../constructors/photoCachedSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$photoEmpty](../constructors/photoEmpty.md) = \['id' => [long](../types/long.md), \];  

[$photoSize](../constructors/photoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'size' => [int](../types/int.md), \];  

[$photoSizeEmpty](../constructors/photoSizeEmpty.md) = \['type' => [string](../types/string.md), \];  

[$photos\_photo](../constructors/photos_photo.md) = \['photo' => [Photo](../types/Photo.md), 'users' => \[[User](../types/User.md)\], \];  

[$photos\_photos](../constructors/photos_photos.md) = \['photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$photos\_photosSlice](../constructors/photos_photosSlice.md) = \['count' => [int](../types/int.md), 'photos' => \[[Photo](../types/Photo.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$privacyKeyChatInvite](../constructors/privacyKeyChatInvite.md) = \[\];  

[$privacyKeyStatusTimestamp](../constructors/privacyKeyStatusTimestamp.md) = \[\];  

[$privacyValueAllowAll](../constructors/privacyValueAllowAll.md) = \[\];  

[$privacyValueAllowContacts](../constructors/privacyValueAllowContacts.md) = \[\];  

[$privacyValueAllowUsers](../constructors/privacyValueAllowUsers.md) = \['users' => \[[int](../types/int.md)\], \];  

[$privacyValueDisallowAll](../constructors/privacyValueDisallowAll.md) = \[\];  

[$privacyValueDisallowContacts](../constructors/privacyValueDisallowContacts.md) = \[\];  

[$privacyValueDisallowUsers](../constructors/privacyValueDisallowUsers.md) = \['users' => \[[int](../types/int.md)\], \];  

[$receivedNotifyMessage](../constructors/receivedNotifyMessage.md) = \['id' => [int](../types/int.md), \];  

[$replyInlineMarkup](../constructors/replyInlineMarkup.md) = \['rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];  

[$replyKeyboardForceReply](../constructors/replyKeyboardForceReply.md) = \['single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \];  

[$replyKeyboardHide](../constructors/replyKeyboardHide.md) = \['selective' => [Bool](../types/Bool.md), \];  

[$replyKeyboardMarkup](../constructors/replyKeyboardMarkup.md) = \['resize' => [Bool](../types/Bool.md), 'single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];  

[$sendMessageCancelAction](../constructors/sendMessageCancelAction.md) = \[\];  

[$sendMessageChooseContactAction](../constructors/sendMessageChooseContactAction.md) = \[\];  

[$sendMessageGamePlayAction](../constructors/sendMessageGamePlayAction.md) = \[\];  

[$sendMessageGeoLocationAction](../constructors/sendMessageGeoLocationAction.md) = \[\];  

[$sendMessageRecordAudioAction](../constructors/sendMessageRecordAudioAction.md) = \[\];  

[$sendMessageRecordVideoAction](../constructors/sendMessageRecordVideoAction.md) = \[\];  

[$sendMessageTypingAction](../constructors/sendMessageTypingAction.md) = \[\];  

[$sendMessageUploadAudioAction](../constructors/sendMessageUploadAudioAction.md) = \['progress' => [int](../types/int.md), \];  

[$sendMessageUploadDocumentAction](../constructors/sendMessageUploadDocumentAction.md) = \['progress' => [int](../types/int.md), \];  

[$sendMessageUploadPhotoAction](../constructors/sendMessageUploadPhotoAction.md) = \['progress' => [int](../types/int.md), \];  

[$sendMessageUploadVideoAction](../constructors/sendMessageUploadVideoAction.md) = \['progress' => [int](../types/int.md), \];  

[$stickerPack](../constructors/stickerPack.md) = \['emoticon' => [string](../types/string.md), 'documents' => \[[long](../types/long.md)\], \];  

[$stickerSet](../constructors/stickerSet.md) = \['installed' => [Bool](../types/Bool.md), 'archived' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'masks' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \];  

[$stickerSetCovered](../constructors/stickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'cover' => [Document](../types/Document.md), \];  

[$stickerSetMultiCovered](../constructors/stickerSetMultiCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'covers' => \[[Document](../types/Document.md)\], \];  

[$storage\_fileGif](../constructors/storage_fileGif.md) = \[\];  

[$storage\_fileJpeg](../constructors/storage_fileJpeg.md) = \[\];  

[$storage\_fileMov](../constructors/storage_fileMov.md) = \[\];  

[$storage\_fileMp3](../constructors/storage_fileMp3.md) = \[\];  

[$storage\_fileMp4](../constructors/storage_fileMp4.md) = \[\];  

[$storage\_filePartial](../constructors/storage_filePartial.md) = \[\];  

[$storage\_filePdf](../constructors/storage_filePdf.md) = \[\];  

[$storage\_filePng](../constructors/storage_filePng.md) = \[\];  

[$storage\_fileUnknown](../constructors/storage_fileUnknown.md) = \[\];  

[$storage\_fileWebp](../constructors/storage_fileWebp.md) = \[\];  

[$topPeer](../constructors/topPeer.md) = \['peer' => [Peer](../types/Peer.md), 'rating' => [double](../types/double.md), \];  

[$topPeerCategoryBotsInline](../constructors/topPeerCategoryBotsInline.md) = \[\];  

[$topPeerCategoryBotsPM](../constructors/topPeerCategoryBotsPM.md) = \[\];  

[$topPeerCategoryChannels](../constructors/topPeerCategoryChannels.md) = \[\];  

[$topPeerCategoryCorrespondents](../constructors/topPeerCategoryCorrespondents.md) = \[\];  

[$topPeerCategoryGroups](../constructors/topPeerCategoryGroups.md) = \[\];  

[$topPeerCategoryPeers](../constructors/topPeerCategoryPeers.md) = \['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'count' => [int](../types/int.md), 'peers' => \[[TopPeer](../types/TopPeer.md)\], \];  

[$true](../constructors/true.md) = \[\];  

[$updateBotCallbackQuery](../constructors/updateBotCallbackQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'peer' => [Peer](../types/Peer.md), 'msg_id' => [int](../types/int.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];  

[$updateBotInlineQuery](../constructors/updateBotInlineQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'offset' => [string](../types/string.md), \];  

[$updateBotInlineSend](../constructors/updateBotInlineSend.md) = \['user_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'id' => [string](../types/string.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), \];  

[$updateChannel](../constructors/updateChannel.md) = \['channel_id' => [int](../types/int.md), \];  

[$updateChannelMessageViews](../constructors/updateChannelMessageViews.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \];  

[$updateChannelPinnedMessage](../constructors/updateChannelPinnedMessage.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), \];  

[$updateChannelTooLong](../constructors/updateChannelTooLong.md) = \['channel_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), \];  

[$updateChatAdmins](../constructors/updateChatAdmins.md) = \['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$updateChatParticipantAdd](../constructors/updateChatParticipantAdd.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$updateChatParticipantAdmin](../constructors/updateChatParticipantAdmin.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'is_admin' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$updateChatParticipantDelete](../constructors/updateChatParticipantDelete.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$updateChatParticipants](../constructors/updateChatParticipants.md) = \['participants' => [ChatParticipants](../types/ChatParticipants.md), \];  

[$updateChatUserTyping](../constructors/updateChatUserTyping.md) = \['chat_id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$updateConfig](../constructors/updateConfig.md) = \[\];  

[$updateContactLink](../constructors/updateContactLink.md) = \['user_id' => [int](../types/int.md), 'my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), \];  

[$updateContactRegistered](../constructors/updateContactRegistered.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$updateDcOptions](../constructors/updateDcOptions.md) = \['dc_options' => \[[DcOption](../types/DcOption.md)\], \];  

[$updateDeleteChannelMessages](../constructors/updateDeleteChannelMessages.md) = \['channel_id' => [int](../types/int.md), 'messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateDeleteMessages](../constructors/updateDeleteMessages.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateDraftMessage](../constructors/updateDraftMessage.md) = \['peer' => [Peer](../types/Peer.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$updateEditChannelMessage](../constructors/updateEditChannelMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateEditMessage](../constructors/updateEditMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateEncryptedChatTyping](../constructors/updateEncryptedChatTyping.md) = \['chat_id' => [int](../types/int.md), \];  

[$updateEncryptedMessagesRead](../constructors/updateEncryptedMessagesRead.md) = \['chat_id' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$updateEncryption](../constructors/updateEncryption.md) = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \];  

[$updateInlineBotCallbackQuery](../constructors/updateInlineBotCallbackQuery.md) = \['query_id' => [long](../types/long.md), 'user_id' => [int](../types/int.md), 'msg_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'chat_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game_short_name' => [string](../types/string.md), \];  

[$updateMessageID](../constructors/updateMessageID.md) = \['id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), \];  

[$updateNewAuthorization](../constructors/updateNewAuthorization.md) = \['auth_key_id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'device' => [string](../types/string.md), 'location' => [string](../types/string.md), \];  

[$updateNewChannelMessage](../constructors/updateNewChannelMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateNewEncryptedMessage](../constructors/updateNewEncryptedMessage.md) = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \];  

[$updateNewMessage](../constructors/updateNewMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateNewStickerSet](../constructors/updateNewStickerSet.md) = \['stickerset' => [messages\_StickerSet](../types/messages_StickerSet.md), \];  

[$updateNotifySettings](../constructors/updateNotifySettings.md) = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];  

[$updatePrivacy](../constructors/updatePrivacy.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];  

[$updatePtsChanged](../constructors/updatePtsChanged.md) = \[\];  

[$updateReadChannelInbox](../constructors/updateReadChannelInbox.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$updateReadChannelOutbox](../constructors/updateReadChannelOutbox.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];  

[$updateReadFeaturedStickers](../constructors/updateReadFeaturedStickers.md) = \[\];  

[$updateReadHistoryInbox](../constructors/updateReadHistoryInbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateReadHistoryOutbox](../constructors/updateReadHistoryOutbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateReadMessagesContents](../constructors/updateReadMessagesContents.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updateRecentStickers](../constructors/updateRecentStickers.md) = \[\];  

[$updateSavedGifs](../constructors/updateSavedGifs.md) = \[\];  

[$updateServiceNotification](../constructors/updateServiceNotification.md) = \['type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'popup' => [Bool](../types/Bool.md), \];  

[$updateShort](../constructors/updateShort.md) = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \];  

[$updateShortChatMessage](../constructors/updateShortChatMessage.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'chat_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$updateShortMessage](../constructors/updateShortMessage.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via_bot_id' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$updateShortSentMessage](../constructors/updateShortSentMessage.md) = \['out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];  

[$updateStickerSets](../constructors/updateStickerSets.md) = \[\];  

[$updateStickerSetsOrder](../constructors/updateStickerSetsOrder.md) = \['masks' => [Bool](../types/Bool.md), 'order' => \[[long](../types/long.md)\], \];  

[$updateUserBlocked](../constructors/updateUserBlocked.md) = \['user_id' => [int](../types/int.md), 'blocked' => [Bool](../types/Bool.md), \];  

[$updateUserName](../constructors/updateUserName.md) = \['user_id' => [int](../types/int.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), \];  

[$updateUserPhone](../constructors/updateUserPhone.md) = \['user_id' => [int](../types/int.md), 'phone' => [string](../types/string.md), \];  

[$updateUserPhoto](../constructors/updateUserPhoto.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'previous' => [Bool](../types/Bool.md), \];  

[$updateUserStatus](../constructors/updateUserStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$updateUserTyping](../constructors/updateUserTyping.md) = \['user_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$updateWebPage](../constructors/updateWebPage.md) = \['webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];  

[$updates](../constructors/updates.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$updatesCombined](../constructors/updatesCombined.md) = \['updates' => \[[Update](../types/Update.md)\], 'users' => \[[User](../types/User.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'date' => [int](../types/int.md), 'seq_start' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$updatesTooLong](../constructors/updatesTooLong.md) = \[\];  

[$updates\_channelDifference](../constructors/updates_channelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'new_messages' => \[[Message](../types/Message.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$updates\_channelDifferenceEmpty](../constructors/updates_channelDifferenceEmpty.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), \];  

[$updates\_channelDifferenceTooLong](../constructors/updates_channelDifferenceTooLong.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'read_outbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];  

[$updates\_difference](../constructors/updates_difference.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];  

[$updates\_differenceEmpty](../constructors/updates_differenceEmpty.md) = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$updates\_differenceSlice](../constructors/updates_differenceSlice.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'intermediate_state' => [updates\_State](../types/updates_State.md), \];  

[$updates\_state](../constructors/updates_state.md) = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), \];  

[$upload\_file](../constructors/upload_file.md) = \['type' => [storage\_FileType](../types/storage_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$user](../constructors/user.md) = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot_chat_history' => [Bool](../types/Bool.md), 'bot_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'bot_inline_geo' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot_info_version' => [int](../types/int.md), 'restriction_reason' => [string](../types/string.md), 'bot_inline_placeholder' => [string](../types/string.md), \];  

[$userEmpty](../constructors/userEmpty.md) = \['id' => [int](../types/int.md), \];  

[$userFull](../constructors/userFull.md) = \['blocked' => [Bool](../types/Bool.md), 'user' => [User](../types/User.md), 'about' => [string](../types/string.md), 'link' => [contacts\_Link](../types/contacts_Link.md), 'profile_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'bot_info' => [BotInfo](../types/BotInfo.md), \];  

[$userProfilePhoto](../constructors/userProfilePhoto.md) = \['photo_id' => [long](../types/long.md), 'photo_small' => [FileLocation](../types/FileLocation.md), 'photo_big' => [FileLocation](../types/FileLocation.md), \];  

[$userProfilePhotoEmpty](../constructors/userProfilePhotoEmpty.md) = \[\];  

[$userStatusEmpty](../constructors/userStatusEmpty.md) = \[\];  

[$userStatusLastMonth](../constructors/userStatusLastMonth.md) = \[\];  

[$userStatusLastWeek](../constructors/userStatusLastWeek.md) = \[\];  

[$userStatusOffline](../constructors/userStatusOffline.md) = \['was_online' => [int](../types/int.md), \];  

[$userStatusOnline](../constructors/userStatusOnline.md) = \['expires' => [int](../types/int.md), \];  

[$userStatusRecently](../constructors/userStatusRecently.md) = \[\];  

[$vector](../constructors/vector.md) = \[\];  

[$wallPaper](../constructors/wallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], 'color' => [int](../types/int.md), \];  

[$wallPaperSolid](../constructors/wallPaperSolid.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \];  

[$webPage](../constructors/webPage.md) = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), \];  

[$webPageEmpty](../constructors/webPageEmpty.md) = \['id' => [long](../types/long.md), \];  

[$webPagePending](../constructors/webPagePending.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \];  

