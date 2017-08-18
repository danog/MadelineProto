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

[$account\_sentChangePhoneCode](../constructors/account_sentChangePhoneCode.md) = \['phone_code_hash' => [string](../types/string.md), 'send_call_timeout' => [int](../types/int.md), \];<a name="account_sentChangePhoneCode"></a>  

***
<br><br>[$audio](../constructors/audio.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'dc_id' => [int](../types/int.md), \];<a name="audio"></a>  

***
<br><br>[$audioEmpty](../constructors/audioEmpty.md) = \['id' => [long](../types/long.md), \];<a name="audioEmpty"></a>  

***
<br><br>[$auth\_authorization](../constructors/auth_authorization.md) = \['user' => [User](../types/User.md), \];<a name="auth_authorization"></a>  

[$auth\_checkedPhone](../constructors/auth_checkedPhone.md) = \['phone_registered' => [Bool](../types/Bool.md), \];<a name="auth_checkedPhone"></a>  

[$auth\_exportedAuthorization](../constructors/auth_exportedAuthorization.md) = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="auth_exportedAuthorization"></a>  

[$auth\_passwordRecovery](../constructors/auth_passwordRecovery.md) = \['email_pattern' => [string](../types/string.md), \];<a name="auth_passwordRecovery"></a>  

[$auth\_sentAppCode](../constructors/auth_sentAppCode.md) = \['phone_registered' => [Bool](../types/Bool.md), 'phone_code_hash' => [string](../types/string.md), 'send_call_timeout' => [int](../types/int.md), 'is_password' => [Bool](../types/Bool.md), \];<a name="auth_sentAppCode"></a>  

[$auth\_sentCode](../constructors/auth_sentCode.md) = \['phone_registered' => [Bool](../types/Bool.md), 'phone_code_hash' => [string](../types/string.md), 'send_call_timeout' => [int](../types/int.md), 'is_password' => [Bool](../types/Bool.md), \];<a name="auth_sentCode"></a>  

***
<br><br>[$authorization](../constructors/authorization.md) = \['hash' => [long](../types/long.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'api_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'date_created' => [int](../types/int.md), 'date_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];<a name="authorization"></a>  

***
<br><br>[$boolFalse](../constructors/boolFalse.md) = \[\];<a name="boolFalse"></a>  

***
<br><br>[$boolTrue](../constructors/boolTrue.md) = \[\];<a name="boolTrue"></a>  

***
<br><br>[$botCommand](../constructors/botCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="botCommand"></a>  

***
<br><br>[$botInfo](../constructors/botInfo.md) = \['user_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'share_text' => [string](../types/string.md), 'description' => [string](../types/string.md), 'commands' => \[[BotCommand](../types/BotCommand.md)\], \];<a name="botInfo"></a>  

***
<br><br>[$botInfoEmpty](../constructors/botInfoEmpty.md) = \[\];<a name="botInfoEmpty"></a>  

***
<br><br>[$channel](../constructors/channel.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'editor' => [Bool](../types/Bool.md), 'moderator' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \];<a name="channel"></a>  

***
<br><br>[$channelForbidden](../constructors/channelForbidden.md) = \['id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), \];<a name="channelForbidden"></a>  

***
<br><br>[$channelFull](../constructors/channelFull.md) = \['can_view_participants' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants_count' => [int](../types/int.md), 'admins_count' => [int](../types/int.md), 'kicked_count' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'unread_important_count' => [int](../types/int.md), 'chat_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot_info' => \[[BotInfo](../types/BotInfo.md)\], 'migrated_from_chat_id' => [int](../types/int.md), 'migrated_from_max_id' => [int](../types/int.md), \];<a name="channelFull"></a>  

***
<br><br>[$channelMessagesFilter](../constructors/channelMessagesFilter.md) = \['important_only' => [Bool](../types/Bool.md), 'exclude_new_messages' => [Bool](../types/Bool.md), 'ranges' => \[[MessageRange](../types/MessageRange.md)\], \];<a name="channelMessagesFilter"></a>  

***
<br><br>[$channelMessagesFilterCollapsed](../constructors/channelMessagesFilterCollapsed.md) = \[\];<a name="channelMessagesFilterCollapsed"></a>  

***
<br><br>[$channelMessagesFilterEmpty](../constructors/channelMessagesFilterEmpty.md) = \[\];<a name="channelMessagesFilterEmpty"></a>  

***
<br><br>[$channelParticipant](../constructors/channelParticipant.md) = \['user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipant"></a>  

***
<br><br>[$channelParticipantCreator](../constructors/channelParticipantCreator.md) = \['user_id' => [int](../types/int.md), \];<a name="channelParticipantCreator"></a>  

***
<br><br>[$channelParticipantEditor](../constructors/channelParticipantEditor.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipantEditor"></a>  

***
<br><br>[$channelParticipantKicked](../constructors/channelParticipantKicked.md) = \['user_id' => [int](../types/int.md), 'kicked_by' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipantKicked"></a>  

***
<br><br>[$channelParticipantModerator](../constructors/channelParticipantModerator.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipantModerator"></a>  

***
<br><br>[$channelParticipantSelf](../constructors/channelParticipantSelf.md) = \['user_id' => [int](../types/int.md), 'inviter_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="channelParticipantSelf"></a>  

***
<br><br>[$channelParticipantsAdmins](../constructors/channelParticipantsAdmins.md) = \[\];<a name="channelParticipantsAdmins"></a>  

***
<br><br>[$channelParticipantsBots](../constructors/channelParticipantsBots.md) = \[\];<a name="channelParticipantsBots"></a>  

***
<br><br>[$channelParticipantsKicked](../constructors/channelParticipantsKicked.md) = \[\];<a name="channelParticipantsKicked"></a>  

***
<br><br>[$channelParticipantsRecent](../constructors/channelParticipantsRecent.md) = \[\];<a name="channelParticipantsRecent"></a>  

***
<br><br>[$channelRoleEditor](../constructors/channelRoleEditor.md) = \[\];<a name="channelRoleEditor"></a>  

***
<br><br>[$channelRoleEmpty](../constructors/channelRoleEmpty.md) = \[\];<a name="channelRoleEmpty"></a>  

***
<br><br>[$channelRoleModerator](../constructors/channelRoleModerator.md) = \[\];<a name="channelRoleModerator"></a>  

***
<br><br>[$channels\_channelParticipant](../constructors/channels_channelParticipant.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => \[[User](../types/User.md)\], \];<a name="channels_channelParticipant"></a>  

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
<br><br>[$chatInvite](../constructors/chatInvite.md) = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), \];<a name="chatInvite"></a>  

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
<br><br>[$config](../constructors/config.md) = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test_mode' => [Bool](../types/Bool.md), 'this_dc' => [int](../types/int.md), 'dc_options' => \[[DcOption](../types/DcOption.md)\], 'chat_size_max' => [int](../types/int.md), 'megagroup_size_max' => [int](../types/int.md), 'forwarded_count_max' => [int](../types/int.md), 'online_update_period_ms' => [int](../types/int.md), 'offline_blur_timeout_ms' => [int](../types/int.md), 'offline_idle_timeout_ms' => [int](../types/int.md), 'online_cloud_timeout_ms' => [int](../types/int.md), 'notify_cloud_delay_ms' => [int](../types/int.md), 'notify_default_delay_ms' => [int](../types/int.md), 'chat_big_size' => [int](../types/int.md), 'push_chat_period_ms' => [int](../types/int.md), 'push_chat_limit' => [int](../types/int.md), 'disabled_features' => \[[DisabledFeature](../types/DisabledFeature.md)\], \];<a name="config"></a>  

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
<br><br>[$contactSuggested](../constructors/contactSuggested.md) = \['user_id' => [int](../types/int.md), 'mutual_contacts' => [int](../types/int.md), \];<a name="contactSuggested"></a>  

***
<br><br>[$contacts\_blocked](../constructors/contacts_blocked.md) = \['blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_blocked"></a>  

[$contacts\_blockedSlice](../constructors/contacts_blockedSlice.md) = \['count' => [int](../types/int.md), 'blocked' => \[[ContactBlocked](../types/ContactBlocked.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_blockedSlice"></a>  

[$contacts\_contacts](../constructors/contacts_contacts.md) = \['contacts' => \[[Contact](../types/Contact.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_contacts"></a>  

[$contacts\_contactsNotModified](../constructors/contacts_contactsNotModified.md) = \[\];<a name="contacts_contactsNotModified"></a>  

[$contacts\_found](../constructors/contacts_found.md) = \['results' => \[[Peer](../types/Peer.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_found"></a>  

[$contacts\_importedContacts](../constructors/contacts_importedContacts.md) = \['imported' => \[[ImportedContact](../types/ImportedContact.md)\], 'retry_contacts' => \[[long](../types/long.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_importedContacts"></a>  

[$contacts\_link](../constructors/contacts_link.md) = \['my_link' => [ContactLink](../types/ContactLink.md), 'foreign_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \];<a name="contacts_link"></a>  

[$contacts\_resolvedPeer](../constructors/contacts_resolvedPeer.md) = \['peer' => [Peer](../types/Peer.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_resolvedPeer"></a>  

[$contacts\_suggested](../constructors/contacts_suggested.md) = \['results' => \[[ContactSuggested](../types/ContactSuggested.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="contacts_suggested"></a>  

***
<br><br>[$dcOption](../constructors/dcOption.md) = \['ipv6' => [Bool](../types/Bool.md), 'media_only' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \];<a name="dcOption"></a>  

***
<br><br>[$dialog](../constructors/dialog.md) = \['peer' => [Peer](../types/Peer.md), 'top_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];<a name="dialog"></a>  

***
<br><br>[$dialogChannel](../constructors/dialogChannel.md) = \['peer' => [Peer](../types/Peer.md), 'top_message' => [int](../types/int.md), 'top_important_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'unread_important_count' => [int](../types/int.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), \];<a name="dialogChannel"></a>  

***
<br><br>[$disabledFeature](../constructors/disabledFeature.md) = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="disabledFeature"></a>  

***
<br><br>[$document](../constructors/document.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc_id' => [int](../types/int.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];<a name="document"></a>  

***
<br><br>[$documentAttributeAnimated](../constructors/documentAttributeAnimated.md) = \[\];<a name="documentAttributeAnimated"></a>  

***
<br><br>[$documentAttributeAudio](../constructors/documentAttributeAudio.md) = \['duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), \];<a name="documentAttributeAudio"></a>  

***
<br><br>[$documentAttributeFilename](../constructors/documentAttributeFilename.md) = \['file_name' => [string](../types/string.md), \];<a name="documentAttributeFilename"></a>  

***
<br><br>[$documentAttributeImageSize](../constructors/documentAttributeImageSize.md) = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="documentAttributeImageSize"></a>  

***
<br><br>[$documentAttributeSticker](../constructors/documentAttributeSticker.md) = \['alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), \];<a name="documentAttributeSticker"></a>  

***
<br><br>[$documentAttributeVideo](../constructors/documentAttributeVideo.md) = \['duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="documentAttributeVideo"></a>  

***
<br><br>[$documentEmpty](../constructors/documentEmpty.md) = \['id' => [long](../types/long.md), \];<a name="documentEmpty"></a>  

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
<br><br>[$fileLocation](../constructors/fileLocation.md) = \['dc_id' => [int](../types/int.md), 'volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];<a name="fileLocation"></a>  

***
<br><br>[$fileLocationUnavailable](../constructors/fileLocationUnavailable.md) = \['volume_id' => [long](../types/long.md), 'local_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];<a name="fileLocationUnavailable"></a>  

***
<br><br>[$geoPoint](../constructors/geoPoint.md) = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \];<a name="geoPoint"></a>  

***
<br><br>[$geoPointEmpty](../constructors/geoPointEmpty.md) = \[\];<a name="geoPointEmpty"></a>  

***
<br><br>[$help\_appChangelog](../constructors/help_appChangelog.md) = \['text' => [string](../types/string.md), \];<a name="help_appChangelog"></a>  

[$help\_appChangelogEmpty](../constructors/help_appChangelogEmpty.md) = \[\];<a name="help_appChangelogEmpty"></a>  

[$help\_appUpdate](../constructors/help_appUpdate.md) = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \];<a name="help_appUpdate"></a>  

[$help\_inviteText](../constructors/help_inviteText.md) = \['message' => [string](../types/string.md), \];<a name="help_inviteText"></a>  

[$help\_noAppUpdate](../constructors/help_noAppUpdate.md) = \[\];<a name="help_noAppUpdate"></a>  

[$help\_support](../constructors/help_support.md) = \['phone_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \];<a name="help_support"></a>  

***
<br><br>[$importedContact](../constructors/importedContact.md) = \['user_id' => [int](../types/int.md), 'client_id' => [long](../types/long.md), \];<a name="importedContact"></a>  

***
<br><br>[$inputAppEvent](../constructors/inputAppEvent.md) = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \];<a name="inputAppEvent"></a>  

***
<br><br>[$inputAudio](../constructors/inputAudio.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputAudio"></a>  

***
<br><br>[$inputAudioEmpty](../constructors/inputAudioEmpty.md) = \[\];<a name="inputAudioEmpty"></a>  

***
<br><br>[$inputAudioFileLocation](../constructors/inputAudioFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputAudioFileLocation"></a>  

***
<br><br>[$inputChannel](../constructors/inputChannel.md) = \['channel_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputChannel"></a>  

***
<br><br>[$inputChannelEmpty](../constructors/inputChannelEmpty.md) = \[\];<a name="inputChannelEmpty"></a>  

***
<br><br>[$inputChatPhoto](../constructors/inputChatPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \];<a name="inputChatPhoto"></a>  

***
<br><br>[$inputChatPhotoEmpty](../constructors/inputChatPhotoEmpty.md) = \[\];<a name="inputChatPhotoEmpty"></a>  

***
<br><br>[$inputChatUploadedPhoto](../constructors/inputChatUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \];<a name="inputChatUploadedPhoto"></a>  

***
<br><br>[$inputDocument](../constructors/inputDocument.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputDocument"></a>  

***
<br><br>[$inputDocumentEmpty](../constructors/inputDocumentEmpty.md) = \[\];<a name="inputDocumentEmpty"></a>  

***
<br><br>[$inputDocumentFileLocation](../constructors/inputDocumentFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputDocumentFileLocation"></a>  

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
<br><br>[$inputGeoPoint](../constructors/inputGeoPoint.md) = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \];<a name="inputGeoPoint"></a>  

***
<br><br>[$inputGeoPointEmpty](../constructors/inputGeoPointEmpty.md) = \[\];<a name="inputGeoPointEmpty"></a>  

***
<br><br>[$inputMediaAudio](../constructors/inputMediaAudio.md) = \['id' => [InputAudio](../types/InputAudio.md), \];<a name="inputMediaAudio"></a>  

***
<br><br>[$inputMediaContact](../constructors/inputMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];<a name="inputMediaContact"></a>  

***
<br><br>[$inputMediaDocument](../constructors/inputMediaDocument.md) = \['id' => [InputDocument](../types/InputDocument.md), \];<a name="inputMediaDocument"></a>  

***
<br><br>[$inputMediaEmpty](../constructors/inputMediaEmpty.md) = \[\];<a name="inputMediaEmpty"></a>  

***
<br><br>[$inputMediaGeoPoint](../constructors/inputMediaGeoPoint.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), \];<a name="inputMediaGeoPoint"></a>  

***
<br><br>[$inputMediaPhoto](../constructors/inputMediaPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaPhoto"></a>  

***
<br><br>[$inputMediaUploadedAudio](../constructors/inputMediaUploadedAudio.md) = \['file' => [InputFile](../types/InputFile.md), 'duration' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), \];<a name="inputMediaUploadedAudio"></a>  

***
<br><br>[$inputMediaUploadedDocument](../constructors/inputMediaUploadedDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];<a name="inputMediaUploadedDocument"></a>  

***
<br><br>[$inputMediaUploadedPhoto](../constructors/inputMediaUploadedPhoto.md) = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaUploadedPhoto"></a>  

***
<br><br>[$inputMediaUploadedThumbDocument](../constructors/inputMediaUploadedThumbDocument.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime_type' => [string](../types/string.md), 'attributes' => \[[DocumentAttribute](../types/DocumentAttribute.md)\], \];<a name="inputMediaUploadedThumbDocument"></a>  

***
<br><br>[$inputMediaUploadedThumbVideo](../constructors/inputMediaUploadedThumbVideo.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaUploadedThumbVideo"></a>  

***
<br><br>[$inputMediaUploadedVideo](../constructors/inputMediaUploadedVideo.md) = \['file' => [InputFile](../types/InputFile.md), 'duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaUploadedVideo"></a>  

***
<br><br>[$inputMediaVenue](../constructors/inputMediaVenue.md) = \['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];<a name="inputMediaVenue"></a>  

***
<br><br>[$inputMediaVideo](../constructors/inputMediaVideo.md) = \['id' => [InputVideo](../types/InputVideo.md), 'caption' => [string](../types/string.md), \];<a name="inputMediaVideo"></a>  

***
<br><br>[$inputMessagesFilterAudio](../constructors/inputMessagesFilterAudio.md) = \[\];<a name="inputMessagesFilterAudio"></a>  

***
<br><br>[$inputMessagesFilterAudioDocuments](../constructors/inputMessagesFilterAudioDocuments.md) = \[\];<a name="inputMessagesFilterAudioDocuments"></a>  

***
<br><br>[$inputMessagesFilterDocument](../constructors/inputMessagesFilterDocument.md) = \[\];<a name="inputMessagesFilterDocument"></a>  

***
<br><br>[$inputMessagesFilterEmpty](../constructors/inputMessagesFilterEmpty.md) = \[\];<a name="inputMessagesFilterEmpty"></a>  

***
<br><br>[$inputMessagesFilterPhotoVideo](../constructors/inputMessagesFilterPhotoVideo.md) = \[\];<a name="inputMessagesFilterPhotoVideo"></a>  

***
<br><br>[$inputMessagesFilterPhotoVideoDocuments](../constructors/inputMessagesFilterPhotoVideoDocuments.md) = \[\];<a name="inputMessagesFilterPhotoVideoDocuments"></a>  

***
<br><br>[$inputMessagesFilterPhotos](../constructors/inputMessagesFilterPhotos.md) = \[\];<a name="inputMessagesFilterPhotos"></a>  

***
<br><br>[$inputMessagesFilterUrl](../constructors/inputMessagesFilterUrl.md) = \[\];<a name="inputMessagesFilterUrl"></a>  

***
<br><br>[$inputMessagesFilterVideo](../constructors/inputMessagesFilterVideo.md) = \[\];<a name="inputMessagesFilterVideo"></a>  

***
<br><br>[$inputNotifyAll](../constructors/inputNotifyAll.md) = \[\];<a name="inputNotifyAll"></a>  

***
<br><br>[$inputNotifyChats](../constructors/inputNotifyChats.md) = \[\];<a name="inputNotifyChats"></a>  

***
<br><br>[$inputNotifyPeer](../constructors/inputNotifyPeer.md) = \['peer' => [InputPeer](../types/InputPeer.md), \];<a name="inputNotifyPeer"></a>  

***
<br><br>[$inputNotifyUsers](../constructors/inputNotifyUsers.md) = \[\];<a name="inputNotifyUsers"></a>  

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
<br><br>[$inputPeerNotifySettings](../constructors/inputPeerNotifySettings.md) = \['mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), 'show_previews' => [Bool](../types/Bool.md), 'events_mask' => [int](../types/int.md), \];<a name="inputPeerNotifySettings"></a>  

***
<br><br>[$inputPeerSelf](../constructors/inputPeerSelf.md) = \[\];<a name="inputPeerSelf"></a>  

***
<br><br>[$inputPeerUser](../constructors/inputPeerUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPeerUser"></a>  

***
<br><br>[$inputPhoneContact](../constructors/inputPhoneContact.md) = \['client_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \];<a name="inputPhoneContact"></a>  

***
<br><br>[$inputPhoto](../constructors/inputPhoto.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputPhoto"></a>  

***
<br><br>[$inputPhotoCrop](../constructors/inputPhotoCrop.md) = \['crop_left' => [double](../types/double.md), 'crop_top' => [double](../types/double.md), 'crop_width' => [double](../types/double.md), \];<a name="inputPhotoCrop"></a>  

***
<br><br>[$inputPhotoCropAuto](../constructors/inputPhotoCropAuto.md) = \[\];<a name="inputPhotoCropAuto"></a>  

***
<br><br>[$inputPhotoEmpty](../constructors/inputPhotoEmpty.md) = \[\];<a name="inputPhotoEmpty"></a>  

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
<br><br>[$inputStickerSetEmpty](../constructors/inputStickerSetEmpty.md) = \[\];<a name="inputStickerSetEmpty"></a>  

***
<br><br>[$inputStickerSetID](../constructors/inputStickerSetID.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputStickerSetID"></a>  

***
<br><br>[$inputStickerSetShortName](../constructors/inputStickerSetShortName.md) = \['short_name' => [string](../types/string.md), \];<a name="inputStickerSetShortName"></a>  

***
<br><br>[$inputUser](../constructors/inputUser.md) = \['user_id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), \];<a name="inputUser"></a>  

***
<br><br>[$inputUserEmpty](../constructors/inputUserEmpty.md) = \[\];<a name="inputUserEmpty"></a>  

***
<br><br>[$inputUserSelf](../constructors/inputUserSelf.md) = \[\];<a name="inputUserSelf"></a>  

***
<br><br>[$inputVideo](../constructors/inputVideo.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputVideo"></a>  

***
<br><br>[$inputVideoEmpty](../constructors/inputVideoEmpty.md) = \[\];<a name="inputVideoEmpty"></a>  

***
<br><br>[$inputVideoFileLocation](../constructors/inputVideoFileLocation.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), \];<a name="inputVideoFileLocation"></a>  

***
<br><br>[$keyboardButton](../constructors/keyboardButton.md) = \['text' => [string](../types/string.md), \];<a name="keyboardButton"></a>  

***
<br><br>[$keyboardButtonRow](../constructors/keyboardButtonRow.md) = \['buttons' => \[[KeyboardButton](../types/KeyboardButton.md)\], \];<a name="keyboardButtonRow"></a>  

***
<br><br>[$message](../constructors/message.md) = \['unread' => [Bool](../types/Bool.md), 'out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'fwd_from_id' => [Peer](../types/Peer.md), 'fwd_date' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], 'views' => [int](../types/int.md), \];<a name="message"></a>  

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
<br><br>[$messageEntityPre](../constructors/messageEntityPre.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];<a name="messageEntityPre"></a>  

***
<br><br>[$messageEntityTextUrl](../constructors/messageEntityTextUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];<a name="messageEntityTextUrl"></a>  

***
<br><br>[$messageEntityUnknown](../constructors/messageEntityUnknown.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityUnknown"></a>  

***
<br><br>[$messageEntityUrl](../constructors/messageEntityUrl.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="messageEntityUrl"></a>  

***
<br><br>[$messageGroup](../constructors/messageGroup.md) = \['min_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'count' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="messageGroup"></a>  

***
<br><br>[$messageMediaAudio](../constructors/messageMediaAudio.md) = \['audio' => [Audio](../types/Audio.md), \];<a name="messageMediaAudio"></a>  

***
<br><br>[$messageMediaContact](../constructors/messageMediaContact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];<a name="messageMediaContact"></a>  

***
<br><br>[$messageMediaDocument](../constructors/messageMediaDocument.md) = \['document' => [Document](../types/Document.md), \];<a name="messageMediaDocument"></a>  

***
<br><br>[$messageMediaEmpty](../constructors/messageMediaEmpty.md) = \[\];<a name="messageMediaEmpty"></a>  

***
<br><br>[$messageMediaGeo](../constructors/messageMediaGeo.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), \];<a name="messageMediaGeo"></a>  

***
<br><br>[$messageMediaPhoto](../constructors/messageMediaPhoto.md) = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \];<a name="messageMediaPhoto"></a>  

***
<br><br>[$messageMediaUnsupported](../constructors/messageMediaUnsupported.md) = \[\];<a name="messageMediaUnsupported"></a>  

***
<br><br>[$messageMediaVenue](../constructors/messageMediaVenue.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue_id' => [string](../types/string.md), \];<a name="messageMediaVenue"></a>  

***
<br><br>[$messageMediaVideo](../constructors/messageMediaVideo.md) = \['video' => [Video](../types/Video.md), 'caption' => [string](../types/string.md), \];<a name="messageMediaVideo"></a>  

***
<br><br>[$messageMediaWebPage](../constructors/messageMediaWebPage.md) = \['webpage' => [WebPage](../types/WebPage.md), \];<a name="messageMediaWebPage"></a>  

***
<br><br>[$messageRange](../constructors/messageRange.md) = \['min_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];<a name="messageRange"></a>  

***
<br><br>[$messageService](../constructors/messageService.md) = \['unread' => [Bool](../types/Bool.md), 'out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'to_id' => [Peer](../types/Peer.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \];<a name="messageService"></a>  

***
<br><br>[$messages\_affectedHistory](../constructors/messages_affectedHistory.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \];<a name="messages_affectedHistory"></a>  

[$messages\_affectedMessages](../constructors/messages_affectedMessages.md) = \['pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="messages_affectedMessages"></a>  

[$messages\_allStickers](../constructors/messages_allStickers.md) = \['hash' => [string](../types/string.md), 'sets' => \[[StickerSet](../types/StickerSet.md)\], \];<a name="messages_allStickers"></a>  

[$messages\_allStickersNotModified](../constructors/messages_allStickersNotModified.md) = \[\];<a name="messages_allStickersNotModified"></a>  

[$messages\_channelMessages](../constructors/messages_channelMessages.md) = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'collapsed' => \[[MessageGroup](../types/MessageGroup.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_channelMessages"></a>  

[$messages\_chatFull](../constructors/messages_chatFull.md) = \['full_chat' => [ChatFull](../types/ChatFull.md), 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_chatFull"></a>  

[$messages\_chats](../constructors/messages_chats.md) = \['chats' => \[[Chat](../types/Chat.md)\], \];<a name="messages_chats"></a>  

[$messages\_dhConfig](../constructors/messages_dhConfig.md) = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \];<a name="messages_dhConfig"></a>  

[$messages\_dhConfigNotModified](../constructors/messages_dhConfigNotModified.md) = \['random' => [bytes](../types/bytes.md), \];<a name="messages_dhConfigNotModified"></a>  

[$messages\_dialogs](../constructors/messages_dialogs.md) = \['dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_dialogs"></a>  

[$messages\_dialogsSlice](../constructors/messages_dialogsSlice.md) = \['count' => [int](../types/int.md), 'dialogs' => \[[Dialog](../types/Dialog.md)\], 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_dialogsSlice"></a>  

[$messages\_messages](../constructors/messages_messages.md) = \['messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_messages"></a>  

[$messages\_messagesSlice](../constructors/messages_messagesSlice.md) = \['count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="messages_messagesSlice"></a>  

[$messages\_sentEncryptedFile](../constructors/messages_sentEncryptedFile.md) = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];<a name="messages_sentEncryptedFile"></a>  

[$messages\_sentEncryptedMessage](../constructors/messages_sentEncryptedMessage.md) = \['date' => [int](../types/int.md), \];<a name="messages_sentEncryptedMessage"></a>  

[$messages\_stickerSet](../constructors/messages_stickerSet.md) = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => \[[StickerPack](../types/StickerPack.md)\], 'documents' => \[[Document](../types/Document.md)\], \];<a name="messages_stickerSet"></a>  

[$messages\_stickers](../constructors/messages_stickers.md) = \['hash' => [string](../types/string.md), 'stickers' => \[[Document](../types/Document.md)\], \];<a name="messages_stickers"></a>  

[$messages\_stickersNotModified](../constructors/messages_stickersNotModified.md) = \[\];<a name="messages_stickersNotModified"></a>  

***
<br><br>[$nearestDc](../constructors/nearestDc.md) = \['country' => [string](../types/string.md), 'this_dc' => [int](../types/int.md), 'nearest_dc' => [int](../types/int.md), \];<a name="nearestDc"></a>  

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
<br><br>[$peerChannel](../constructors/peerChannel.md) = \['channel_id' => [int](../types/int.md), \];<a name="peerChannel"></a>  

***
<br><br>[$peerChat](../constructors/peerChat.md) = \['chat_id' => [int](../types/int.md), \];<a name="peerChat"></a>  

***
<br><br>[$peerNotifyEventsAll](../constructors/peerNotifyEventsAll.md) = \[\];<a name="peerNotifyEventsAll"></a>  

***
<br><br>[$peerNotifyEventsEmpty](../constructors/peerNotifyEventsEmpty.md) = \[\];<a name="peerNotifyEventsEmpty"></a>  

***
<br><br>[$peerNotifySettings](../constructors/peerNotifySettings.md) = \['mute_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), 'show_previews' => [Bool](../types/Bool.md), 'events_mask' => [int](../types/int.md), \];<a name="peerNotifySettings"></a>  

***
<br><br>[$peerNotifySettingsEmpty](../constructors/peerNotifySettingsEmpty.md) = \[\];<a name="peerNotifySettingsEmpty"></a>  

***
<br><br>[$peerUser](../constructors/peerUser.md) = \['user_id' => [int](../types/int.md), \];<a name="peerUser"></a>  

***
<br><br>[$photo](../constructors/photo.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], \];<a name="photo"></a>  

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
<br><br>[$replyKeyboardForceReply](../constructors/replyKeyboardForceReply.md) = \['single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \];<a name="replyKeyboardForceReply"></a>  

***
<br><br>[$replyKeyboardHide](../constructors/replyKeyboardHide.md) = \['selective' => [Bool](../types/Bool.md), \];<a name="replyKeyboardHide"></a>  

***
<br><br>[$replyKeyboardMarkup](../constructors/replyKeyboardMarkup.md) = \['resize' => [Bool](../types/Bool.md), 'single_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => \[[KeyboardButtonRow](../types/KeyboardButtonRow.md)\], \];<a name="replyKeyboardMarkup"></a>  

***
<br><br>[$sendMessageCancelAction](../constructors/sendMessageCancelAction.md) = \[\];<a name="sendMessageCancelAction"></a>  

***
<br><br>[$sendMessageChooseContactAction](../constructors/sendMessageChooseContactAction.md) = \[\];<a name="sendMessageChooseContactAction"></a>  

***
<br><br>[$sendMessageGeoLocationAction](../constructors/sendMessageGeoLocationAction.md) = \[\];<a name="sendMessageGeoLocationAction"></a>  

***
<br><br>[$sendMessageRecordAudioAction](../constructors/sendMessageRecordAudioAction.md) = \[\];<a name="sendMessageRecordAudioAction"></a>  

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
<br><br>[$sendMessageUploadVideoAction](../constructors/sendMessageUploadVideoAction.md) = \['progress' => [int](../types/int.md), \];<a name="sendMessageUploadVideoAction"></a>  

***
<br><br>[$stickerPack](../constructors/stickerPack.md) = \['emoticon' => [string](../types/string.md), 'documents' => \[[long](../types/long.md)\], \];<a name="stickerPack"></a>  

***
<br><br>[$stickerSet](../constructors/stickerSet.md) = \['installed' => [Bool](../types/Bool.md), 'disabled' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \];<a name="stickerSet"></a>  

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
<br><br>[$true](../constructors/true.md) = \[\];<a name="true"></a>  

***
<br><br>[$updateChannel](../constructors/updateChannel.md) = \['channel_id' => [int](../types/int.md), \];<a name="updateChannel"></a>  

***
<br><br>[$updateChannelGroup](../constructors/updateChannelGroup.md) = \['channel_id' => [int](../types/int.md), 'group' => [MessageGroup](../types/MessageGroup.md), \];<a name="updateChannelGroup"></a>  

***
<br><br>[$updateChannelMessageViews](../constructors/updateChannelMessageViews.md) = \['channel_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \];<a name="updateChannelMessageViews"></a>  

***
<br><br>[$updateChannelTooLong](../constructors/updateChannelTooLong.md) = \['channel_id' => [int](../types/int.md), \];<a name="updateChannelTooLong"></a>  

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
<br><br>[$updateEncryptedChatTyping](../constructors/updateEncryptedChatTyping.md) = \['chat_id' => [int](../types/int.md), \];<a name="updateEncryptedChatTyping"></a>  

***
<br><br>[$updateEncryptedMessagesRead](../constructors/updateEncryptedMessagesRead.md) = \['chat_id' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="updateEncryptedMessagesRead"></a>  

***
<br><br>[$updateEncryption](../constructors/updateEncryption.md) = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \];<a name="updateEncryption"></a>  

***
<br><br>[$updateMessageID](../constructors/updateMessageID.md) = \['id' => [int](../types/int.md), \];<a name="updateMessageID"></a>  

***
<br><br>[$updateNewAuthorization](../constructors/updateNewAuthorization.md) = \['auth_key_id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'device' => [string](../types/string.md), 'location' => [string](../types/string.md), \];<a name="updateNewAuthorization"></a>  

***
<br><br>[$updateNewChannelMessage](../constructors/updateNewChannelMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateNewChannelMessage"></a>  

***
<br><br>[$updateNewEncryptedMessage](../constructors/updateNewEncryptedMessage.md) = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \];<a name="updateNewEncryptedMessage"></a>  

***
<br><br>[$updateNewMessage](../constructors/updateNewMessage.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateNewMessage"></a>  

***
<br><br>[$updateNotifySettings](../constructors/updateNotifySettings.md) = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];<a name="updateNotifySettings"></a>  

***
<br><br>[$updatePrivacy](../constructors/updatePrivacy.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];<a name="updatePrivacy"></a>  

***
<br><br>[$updateReadChannelInbox](../constructors/updateReadChannelInbox.md) = \['channel_id' => [int](../types/int.md), 'max_id' => [int](../types/int.md), \];<a name="updateReadChannelInbox"></a>  

***
<br><br>[$updateReadHistoryInbox](../constructors/updateReadHistoryInbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadHistoryInbox"></a>  

***
<br><br>[$updateReadHistoryOutbox](../constructors/updateReadHistoryOutbox.md) = \['peer' => [Peer](../types/Peer.md), 'max_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadHistoryOutbox"></a>  

***
<br><br>[$updateReadMessagesContents](../constructors/updateReadMessagesContents.md) = \['messages' => \[[int](../types/int.md)\], 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), \];<a name="updateReadMessagesContents"></a>  

***
<br><br>[$updateServiceNotification](../constructors/updateServiceNotification.md) = \['type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'popup' => [Bool](../types/Bool.md), \];<a name="updateServiceNotification"></a>  

***
<br><br>[$updateShort](../constructors/updateShort.md) = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \];<a name="updateShort"></a>  

***
<br><br>[$updateShortChatMessage](../constructors/updateShortChatMessage.md) = \['unread' => [Bool](../types/Bool.md), 'out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from_id' => [int](../types/int.md), 'chat_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from_id' => [Peer](../types/Peer.md), 'fwd_date' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortChatMessage"></a>  

***
<br><br>[$updateShortMessage](../constructors/updateShortMessage.md) = \['unread' => [Bool](../types/Bool.md), 'out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media_unread' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd_from_id' => [Peer](../types/Peer.md), 'fwd_date' => [int](../types/int.md), 'reply_to_msg_id' => [int](../types/int.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortMessage"></a>  

***
<br><br>[$updateShortSentMessage](../constructors/updateShortSentMessage.md) = \['unread' => [Bool](../types/Bool.md), 'out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \];<a name="updateShortSentMessage"></a>  

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

[$updates\_channelDifferenceTooLong](../constructors/updates_channelDifferenceTooLong.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top_message' => [int](../types/int.md), 'top_important_message' => [int](../types/int.md), 'read_inbox_max_id' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), 'unread_important_count' => [int](../types/int.md), 'messages' => \[[Message](../types/Message.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], \];<a name="updates_channelDifferenceTooLong"></a>  

[$updates\_difference](../constructors/updates_difference.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'state' => [updates\_State](../types/updates_State.md), \];<a name="updates_difference"></a>  

[$updates\_differenceEmpty](../constructors/updates_differenceEmpty.md) = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];<a name="updates_differenceEmpty"></a>  

[$updates\_differenceSlice](../constructors/updates_differenceSlice.md) = \['new_messages' => \[[Message](../types/Message.md)\], 'new_encrypted_messages' => \[[EncryptedMessage](../types/EncryptedMessage.md)\], 'other_updates' => \[[Update](../types/Update.md)\], 'chats' => \[[Chat](../types/Chat.md)\], 'users' => \[[User](../types/User.md)\], 'intermediate_state' => [updates\_State](../types/updates_State.md), \];<a name="updates_differenceSlice"></a>  

[$updates\_state](../constructors/updates_state.md) = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread_count' => [int](../types/int.md), \];<a name="updates_state"></a>  

***
<br><br>[$upload\_file](../constructors/upload_file.md) = \['type' => [storage\_FileType](../types/storage_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];<a name="upload_file"></a>  

***
<br><br>[$user](../constructors/user.md) = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot_chat_history' => [Bool](../types/Bool.md), 'bot_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access_hash' => [long](../types/long.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot_info_version' => [int](../types/int.md), \];<a name="user"></a>  

***
<br><br>[$userEmpty](../constructors/userEmpty.md) = \['id' => [int](../types/int.md), \];<a name="userEmpty"></a>  

***
<br><br>[$userFull](../constructors/userFull.md) = \['user' => [User](../types/User.md), 'link' => [contacts\_Link](../types/contacts_Link.md), 'profile_photo' => [Photo](../types/Photo.md), 'notify_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'blocked' => [Bool](../types/Bool.md), 'bot_info' => [BotInfo](../types/BotInfo.md), \];<a name="userFull"></a>  

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
<br><br>[$vector](../constructors/vector.md) = \[\];<a name="vector"></a>  

***
<br><br>[$video](../constructors/video.md) = \['id' => [long](../types/long.md), 'access_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc_id' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];<a name="video"></a>  

***
<br><br>[$videoEmpty](../constructors/videoEmpty.md) = \['id' => [long](../types/long.md), \];<a name="videoEmpty"></a>  

***
<br><br>[$wallPaper](../constructors/wallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => \[[PhotoSize](../types/PhotoSize.md)\], 'color' => [int](../types/int.md), \];<a name="wallPaper"></a>  

***
<br><br>[$wallPaperSolid](../constructors/wallPaperSolid.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \];<a name="wallPaperSolid"></a>  

***
<br><br>[$webPage](../constructors/webPage.md) = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), \];<a name="webPage"></a>  

***
<br><br>[$webPageEmpty](../constructors/webPageEmpty.md) = \['id' => [long](../types/long.md), \];<a name="webPageEmpty"></a>  

***
<br><br>[$webPagePending](../constructors/webPagePending.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \];<a name="webPagePending"></a>  

