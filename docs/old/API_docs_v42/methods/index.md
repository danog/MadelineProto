---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)



***
<br><br>$MadelineProto->[account_changePhone](account_changePhone.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$User](../types/User.md)<a name="account_changePhone"></a>  

$MadelineProto->[account_checkUsername](account_checkUsername.md)(\['username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="account_checkUsername"></a>  

$MadelineProto->[account_deleteAccount](account_deleteAccount.md)(\['reason' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="account_deleteAccount"></a>  

$MadelineProto->[account_getAccountTTL](account_getAccountTTL.md)(\[\]) == [$AccountDaysTTL](../types/AccountDaysTTL.md)<a name="account_getAccountTTL"></a>  

$MadelineProto->[account_getAuthorizations](account_getAuthorizations.md)(\[\]) == [$account\_Authorizations](../types/account_Authorizations.md)<a name="account_getAuthorizations"></a>  

$MadelineProto->[account_getNotifySettings](account_getNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), \]) == [$PeerNotifySettings](../types/PeerNotifySettings.md)<a name="account_getNotifySettings"></a>  

$MadelineProto->[account_getPassword](account_getPassword.md)(\[\]) == [$account\_Password](../types/account_Password.md)<a name="account_getPassword"></a>  

$MadelineProto->[account_getPasswordSettings](account_getPasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), \]) == [$account\_PasswordSettings](../types/account_PasswordSettings.md)<a name="account_getPasswordSettings"></a>  

$MadelineProto->[account_getPrivacy](account_getPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)<a name="account_getPrivacy"></a>  

$MadelineProto->[account_getWallPapers](account_getWallPapers.md)(\[\]) == [$Vector\_of\_WallPaper](../types/WallPaper.md)<a name="account_getWallPapers"></a>  

$MadelineProto->[account_registerDevice](account_registerDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'app_sandbox' => [Bool](../types/Bool.md), 'lang_code' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="account_registerDevice"></a>  

$MadelineProto->[account_resetAuthorization](account_resetAuthorization.md)(\['hash' => [long](../types/long.md), \]) == [$Bool](../types/Bool.md)<a name="account_resetAuthorization"></a>  

$MadelineProto->[account_resetNotifySettings](account_resetNotifySettings.md)(\[\]) == [$Bool](../types/Bool.md)<a name="account_resetNotifySettings"></a>  

$MadelineProto->[account_sendChangePhoneCode](account_sendChangePhoneCode.md)(\['phone_number' => [string](../types/string.md), \]) == [$account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)<a name="account_sendChangePhoneCode"></a>  

$MadelineProto->[account_setAccountTTL](account_setAccountTTL.md)(\['ttl' => [AccountDaysTTL](../types/AccountDaysTTL.md), \]) == [$Bool](../types/Bool.md)<a name="account_setAccountTTL"></a>  

$MadelineProto->[account_setPrivacy](account_setPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), 'rules' => \[[InputPrivacyRule](../types/InputPrivacyRule.md)\], \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)<a name="account_setPrivacy"></a>  

$MadelineProto->[account_unregisterDevice](account_unregisterDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="account_unregisterDevice"></a>  

$MadelineProto->[account_updateDeviceLocked](account_updateDeviceLocked.md)(\['period' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)<a name="account_updateDeviceLocked"></a>  

$MadelineProto->[account_updateNotifySettings](account_updateNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), 'settings' => [InputPeerNotifySettings](../types/InputPeerNotifySettings.md), \]) == [$Bool](../types/Bool.md)<a name="account_updateNotifySettings"></a>  

$MadelineProto->[account_updatePasswordSettings](account_updatePasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), 'new_settings' => [account\_PasswordInputSettings](../types/account_PasswordInputSettings.md), \]) == [$Bool](../types/Bool.md)<a name="account_updatePasswordSettings"></a>  

$MadelineProto->[account_updateProfile](account_updateProfile.md)(\['first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) == [$User](../types/User.md)<a name="account_updateProfile"></a>  

$MadelineProto->[account_updateStatus](account_updateStatus.md)(\['offline' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)<a name="account_updateStatus"></a>  

$MadelineProto->[account_updateUsername](account_updateUsername.md)(\['username' => [string](../types/string.md), \]) == [$User](../types/User.md)<a name="account_updateUsername"></a>  

***
<br><br>$MadelineProto->[auth_bindTempAuthKey](auth_bindTempAuthKey.md)(\['perm_auth_key_id' => [long](../types/long.md), 'nonce' => [long](../types/long.md), 'expires_at' => [int](../types/int.md), 'encrypted_message' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)<a name="auth_bindTempAuthKey"></a>  

$MadelineProto->[auth_checkPassword](auth_checkPassword.md)(\['password_hash' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_checkPassword"></a>  

$MadelineProto->[auth_checkPhone](auth_checkPhone.md)(\['phone_number' => [string](../types/string.md), \]) == [$auth\_CheckedPhone](../types/auth_CheckedPhone.md)<a name="auth_checkPhone"></a>  

$MadelineProto->[auth_exportAuthorization](auth_exportAuthorization.md)(\['dc_id' => [int](../types/int.md), \]) == [$auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)<a name="auth_exportAuthorization"></a>  

$MadelineProto->[auth_importAuthorization](auth_importAuthorization.md)(\['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_importAuthorization"></a>  

$MadelineProto->[auth_importBotAuthorization](auth_importBotAuthorization.md)(\['api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), 'bot_auth_token' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_importBotAuthorization"></a>  

$MadelineProto->[auth_logOut](auth_logOut.md)(\[\]) == [$Bool](../types/Bool.md)<a name="auth_logOut"></a>  

$MadelineProto->[auth_recoverPassword](auth_recoverPassword.md)(\['code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_recoverPassword"></a>  

$MadelineProto->[auth_requestPasswordRecovery](auth_requestPasswordRecovery.md)(\[\]) == [$auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)<a name="auth_requestPasswordRecovery"></a>  

$MadelineProto->[auth_resetAuthorizations](auth_resetAuthorizations.md)(\[\]) == [$Bool](../types/Bool.md)<a name="auth_resetAuthorizations"></a>  

$MadelineProto->[auth_sendCall](auth_sendCall.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="auth_sendCall"></a>  

$MadelineProto->[auth_sendCode](auth_sendCode.md)(\['phone_number' => [string](../types/string.md), 'sms_type' => [int](../types/int.md), 'api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)<a name="auth_sendCode"></a>  

$MadelineProto->[auth_sendInvites](auth_sendInvites.md)(\['phone_numbers' => \[[string](../types/string.md)\], 'message' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="auth_sendInvites"></a>  

$MadelineProto->[auth_sendSms](auth_sendSms.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="auth_sendSms"></a>  

$MadelineProto->[auth_signIn](auth_signIn.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_signIn"></a>  

$MadelineProto->[auth_signUp](auth_signUp.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_signUp"></a>  

***
<br><br>$MadelineProto->[channels_checkUsername](channels_checkUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="channels_checkUsername"></a>  

$MadelineProto->[channels_createChannel](channels_createChannel.md)(\['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="channels_createChannel"></a>  

$MadelineProto->[channels_deleteChannel](channels_deleteChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)<a name="channels_deleteChannel"></a>  

$MadelineProto->[channels_deleteMessages](channels_deleteMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="channels_deleteMessages"></a>  

$MadelineProto->[channels_deleteUserHistory](channels_deleteUserHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)<a name="channels_deleteUserHistory"></a>  

$MadelineProto->[channels_editAbout](channels_editAbout.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'about' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="channels_editAbout"></a>  

$MadelineProto->[channels_editAdmin](channels_editAdmin.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'role' => [ChannelParticipantRole](../types/ChannelParticipantRole.md), \]) == [$Updates](../types/Updates.md)<a name="channels_editAdmin"></a>  

$MadelineProto->[channels_editPhoto](channels_editPhoto.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)<a name="channels_editPhoto"></a>  

$MadelineProto->[channels_editTitle](channels_editTitle.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="channels_editTitle"></a>  

$MadelineProto->[channels_exportInvite](channels_exportInvite.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)<a name="channels_exportInvite"></a>  

$MadelineProto->[channels_getChannels](channels_getChannels.md)(\['id' => \[[InputChannel](../types/InputChannel.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)<a name="channels_getChannels"></a>  

$MadelineProto->[channels_getDialogs](channels_getDialogs.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Dialogs](../types/messages_Dialogs.md)<a name="channels_getDialogs"></a>  

$MadelineProto->[channels_getFullChannel](channels_getFullChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)<a name="channels_getFullChannel"></a>  

$MadelineProto->[channels_getImportantHistory](channels_getImportantHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'offset_id' => [int](../types/int.md), 'add_offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'min_id' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="channels_getImportantHistory"></a>  

$MadelineProto->[channels_getMessages](channels_getMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="channels_getMessages"></a>  

$MadelineProto->[channels_getParticipant](channels_getParticipant.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)<a name="channels_getParticipant"></a>  

$MadelineProto->[channels_getParticipants](channels_getParticipants.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)<a name="channels_getParticipants"></a>  

$MadelineProto->[channels_inviteToChannel](channels_inviteToChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'users' => \[[InputUser](../types/InputUser.md)\], \]) == [$Updates](../types/Updates.md)<a name="channels_inviteToChannel"></a>  

$MadelineProto->[channels_joinChannel](channels_joinChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)<a name="channels_joinChannel"></a>  

$MadelineProto->[channels_kickFromChannel](channels_kickFromChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'kicked' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)<a name="channels_kickFromChannel"></a>  

$MadelineProto->[channels_leaveChannel](channels_leaveChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)<a name="channels_leaveChannel"></a>  

$MadelineProto->[channels_readHistory](channels_readHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'max_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)<a name="channels_readHistory"></a>  

$MadelineProto->[channels_reportSpam](channels_reportSpam.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'id' => \[[int](../types/int.md)\], \]) == [$Bool](../types/Bool.md)<a name="channels_reportSpam"></a>  

$MadelineProto->[channels_toggleComments](channels_toggleComments.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)<a name="channels_toggleComments"></a>  

$MadelineProto->[channels_updateUsername](channels_updateUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)<a name="channels_updateUsername"></a>  

***
<br><br>$MadelineProto->[contacts_block](contacts_block.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)<a name="contacts_block"></a>  

$MadelineProto->[contacts_deleteContact](contacts_deleteContact.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$contacts\_Link](../types/contacts_Link.md)<a name="contacts_deleteContact"></a>  

$MadelineProto->[contacts_deleteContacts](contacts_deleteContacts.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Bool](../types/Bool.md)<a name="contacts_deleteContacts"></a>  

$MadelineProto->[contacts_exportCard](contacts_exportCard.md)(\[\]) == [$Vector\_of\_int](../types/int.md)<a name="contacts_exportCard"></a>  

$MadelineProto->[contacts_getBlocked](contacts_getBlocked.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Blocked](../types/contacts_Blocked.md)<a name="contacts_getBlocked"></a>  

$MadelineProto->[contacts_getContacts](contacts_getContacts.md)(\['hash' => [string](../types/string.md), \]) == [$contacts\_Contacts](../types/contacts_Contacts.md)<a name="contacts_getContacts"></a>  

$MadelineProto->[contacts_getStatuses](contacts_getStatuses.md)(\[\]) == [$Vector\_of\_ContactStatus](../types/ContactStatus.md)<a name="contacts_getStatuses"></a>  

$MadelineProto->[contacts_getSuggested](contacts_getSuggested.md)(\['limit' => [int](../types/int.md), \]) == [$contacts\_Suggested](../types/contacts_Suggested.md)<a name="contacts_getSuggested"></a>  

$MadelineProto->[contacts_importCard](contacts_importCard.md)(\['export_card' => \[[int](../types/int.md)\], \]) == [$User](../types/User.md)<a name="contacts_importCard"></a>  

$MadelineProto->[contacts_importContacts](contacts_importContacts.md)(\['contacts' => \[[InputContact](../types/InputContact.md)\], 'replace' => [Bool](../types/Bool.md), \]) == [$contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)<a name="contacts_importContacts"></a>  

$MadelineProto->[contacts_resolveUsername](contacts_resolveUsername.md)(\['username' => [string](../types/string.md), \]) == [$contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)<a name="contacts_resolveUsername"></a>  

$MadelineProto->[contacts_search](contacts_search.md)(\['q' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Found](../types/contacts_Found.md)<a name="contacts_search"></a>  

$MadelineProto->[contacts_unblock](contacts_unblock.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)<a name="contacts_unblock"></a>  

***
<br><br>$MadelineProto->[help_getAppChangelog](help_getAppChangelog.md)(\['device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \]) == [$help\_AppChangelog](../types/help_AppChangelog.md)<a name="help_getAppChangelog"></a>  

$MadelineProto->[help_getAppUpdate](help_getAppUpdate.md)(\['device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \]) == [$help\_AppUpdate](../types/help_AppUpdate.md)<a name="help_getAppUpdate"></a>  

$MadelineProto->[help_getConfig](help_getConfig.md)(\[\]) == [$Config](../types/Config.md)<a name="help_getConfig"></a>  

$MadelineProto->[help_getInviteText](help_getInviteText.md)(\['lang_code' => [string](../types/string.md), \]) == [$help\_InviteText](../types/help_InviteText.md)<a name="help_getInviteText"></a>  

$MadelineProto->[help_getNearestDc](help_getNearestDc.md)(\[\]) == [$NearestDc](../types/NearestDc.md)<a name="help_getNearestDc"></a>  

$MadelineProto->[help_getSupport](help_getSupport.md)(\[\]) == [$help\_Support](../types/help_Support.md)<a name="help_getSupport"></a>  

$MadelineProto->[help_saveAppLog](help_saveAppLog.md)(\['events' => \[[InputAppEvent](../types/InputAppEvent.md)\], \]) == [$Bool](../types/Bool.md)<a name="help_saveAppLog"></a>  

***
<br><br>$MadelineProto->[initConnection](initConnection.md)(\['api_id' => [int](../types/int.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)<a name="initConnection"></a>  

***
<br><br>$MadelineProto->[invokeAfterMsg](invokeAfterMsg.md)(\['msg_id' => [long](../types/long.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)<a name="invokeAfterMsg"></a>  

***
<br><br>$MadelineProto->[invokeAfterMsgs](invokeAfterMsgs.md)(\['msg_ids' => \[[long](../types/long.md)\], 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)<a name="invokeAfterMsgs"></a>  

***
<br><br>$MadelineProto->[invokeWithLayer](invokeWithLayer.md)(\['layer' => [int](../types/int.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)<a name="invokeWithLayer"></a>  

***
<br><br>$MadelineProto->[invokeWithoutUpdates](invokeWithoutUpdates.md)(\['query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)<a name="invokeWithoutUpdates"></a>  

***
<br><br>$MadelineProto->[messages_acceptEncryption](messages_acceptEncryption.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'g_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)<a name="messages_acceptEncryption"></a>  

$MadelineProto->[messages_addChatUser](messages_addChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'fwd_limit' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)<a name="messages_addChatUser"></a>  

$MadelineProto->[messages_checkChatInvite](messages_checkChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$ChatInvite](../types/ChatInvite.md)<a name="messages_checkChatInvite"></a>  

$MadelineProto->[messages_createChat](messages_createChat.md)(\['users' => \[[InputUser](../types/InputUser.md)\], 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="messages_createChat"></a>  

$MadelineProto->[messages_deleteChatUser](messages_deleteChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$Updates](../types/Updates.md)<a name="messages_deleteChatUser"></a>  

$MadelineProto->[messages_deleteHistory](messages_deleteHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)<a name="messages_deleteHistory"></a>  

$MadelineProto->[messages_deleteMessages](messages_deleteMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="messages_deleteMessages"></a>  

$MadelineProto->[messages_discardEncryption](messages_discardEncryption.md)(\['chat_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)<a name="messages_discardEncryption"></a>  

$MadelineProto->[messages_editChatAdmin](messages_editChatAdmin.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'is_admin' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)<a name="messages_editChatAdmin"></a>  

$MadelineProto->[messages_editChatPhoto](messages_editChatPhoto.md)(\['chat_id' => [int](../types/int.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)<a name="messages_editChatPhoto"></a>  

$MadelineProto->[messages_editChatTitle](messages_editChatTitle.md)(\['chat_id' => [int](../types/int.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="messages_editChatTitle"></a>  

$MadelineProto->[messages_exportChatInvite](messages_exportChatInvite.md)(\['chat_id' => [int](../types/int.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)<a name="messages_exportChatInvite"></a>  

$MadelineProto->[messages_forwardMessage](messages_forwardMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)<a name="messages_forwardMessage"></a>  

$MadelineProto->[messages_forwardMessages](messages_forwardMessages.md)(\['broadcast' => [Bool](../types/Bool.md), 'from_peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'to_peer' => [InputPeer](../types/InputPeer.md), \]) == [$Updates](../types/Updates.md)<a name="messages_forwardMessages"></a>  

$MadelineProto->[messages_getAllStickers](messages_getAllStickers.md)(\['hash' => [string](../types/string.md), \]) == [$messages\_AllStickers](../types/messages_AllStickers.md)<a name="messages_getAllStickers"></a>  

$MadelineProto->[messages_getChats](messages_getChats.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)<a name="messages_getChats"></a>  

$MadelineProto->[messages_getDhConfig](messages_getDhConfig.md)(\['version' => [int](../types/int.md), 'random_length' => [int](../types/int.md), \]) == [$messages\_DhConfig](../types/messages_DhConfig.md)<a name="messages_getDhConfig"></a>  

$MadelineProto->[messages_getDialogs](messages_getDialogs.md)(\['offset_date' => [int](../types/int.md), 'offset_id' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Dialogs](../types/messages_Dialogs.md)<a name="messages_getDialogs"></a>  

$MadelineProto->[messages_getFullChat](messages_getFullChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)<a name="messages_getFullChat"></a>  

$MadelineProto->[messages_getHistory](messages_getHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'add_offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'min_id' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="messages_getHistory"></a>  

$MadelineProto->[messages_getMessages](messages_getMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="messages_getMessages"></a>  

$MadelineProto->[messages_getMessagesViews](messages_getMessagesViews.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'increment' => [Bool](../types/Bool.md), \]) == [$Vector\_of\_int](../types/int.md)<a name="messages_getMessagesViews"></a>  

$MadelineProto->[messages_getStickerSet](messages_getStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$messages\_StickerSet](../types/messages_StickerSet.md)<a name="messages_getStickerSet"></a>  

$MadelineProto->[messages_getStickers](messages_getStickers.md)(\['emoticon' => [string](../types/string.md), 'hash' => [string](../types/string.md), \]) == [$messages\_Stickers](../types/messages_Stickers.md)<a name="messages_getStickers"></a>  

$MadelineProto->[messages_getWebPagePreview](messages_getWebPagePreview.md)(\['message' => [string](../types/string.md), \]) == [$MessageMedia](../types/MessageMedia.md)<a name="messages_getWebPagePreview"></a>  

$MadelineProto->[messages_importChatInvite](messages_importChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="messages_importChatInvite"></a>  

$MadelineProto->[messages_installStickerSet](messages_installStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'disabled' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)<a name="messages_installStickerSet"></a>  

$MadelineProto->[messages_migrateChat](messages_migrateChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)<a name="messages_migrateChat"></a>  

$MadelineProto->[messages_readEncryptedHistory](messages_readEncryptedHistory.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'max_date' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)<a name="messages_readEncryptedHistory"></a>  

$MadelineProto->[messages_readHistory](messages_readHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="messages_readHistory"></a>  

$MadelineProto->[messages_readMessageContents](messages_readMessageContents.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="messages_readMessageContents"></a>  

$MadelineProto->[messages_receivedMessages](messages_receivedMessages.md)(\['max_id' => [int](../types/int.md), \]) == [$Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)<a name="messages_receivedMessages"></a>  

$MadelineProto->[messages_receivedQueue](messages_receivedQueue.md)(\['max_qts' => [int](../types/int.md), \]) == [$Vector\_of\_long](../types/long.md)<a name="messages_receivedQueue"></a>  

$MadelineProto->[messages_reportSpam](messages_reportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)<a name="messages_reportSpam"></a>  

$MadelineProto->[messages_requestEncryption](messages_requestEncryption.md)(\['user_id' => [InputUser](../types/InputUser.md), 'g_a' => [bytes](../types/bytes.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)<a name="messages_requestEncryption"></a>  

$MadelineProto->[messages_search](messages_search.md)(\['important_only' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min_date' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="messages_search"></a>  

$MadelineProto->[messages_searchGlobal](messages_searchGlobal.md)(\['q' => [string](../types/string.md), 'offset_date' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)<a name="messages_searchGlobal"></a>  

$MadelineProto->[messages_sendBroadcast](messages_sendBroadcast.md)(\['contacts' => \[[InputUser](../types/InputUser.md)\], 'message' => [string](../types/string.md), 'media' => [InputMedia](../types/InputMedia.md), \]) == [$Updates](../types/Updates.md)<a name="messages_sendBroadcast"></a>  

$MadelineProto->[messages_sendEncrypted](messages_sendEncrypted.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncrypted"></a>  

$MadelineProto->[messages_sendEncryptedFile](messages_sendEncryptedFile.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'data' => [bytes](../types/bytes.md), 'file' => [InputEncryptedFile](../types/InputEncryptedFile.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncryptedFile"></a>  

$MadelineProto->[messages_sendEncryptedService](messages_sendEncryptedService.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncryptedService"></a>  

$MadelineProto->[messages_sendMedia](messages_sendMedia.md)(\['broadcast' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'media' => [InputMedia](../types/InputMedia.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) == [$Updates](../types/Updates.md)<a name="messages_sendMedia"></a>  

$MadelineProto->[messages_sendMessage](messages_sendMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Updates](../types/Updates.md)<a name="messages_sendMessage"></a>  

$MadelineProto->[messages_setEncryptedTyping](messages_setEncryptedTyping.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'typing' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)<a name="messages_setEncryptedTyping"></a>  

$MadelineProto->[messages_setTyping](messages_setTyping.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) == [$Bool](../types/Bool.md)<a name="messages_setTyping"></a>  

$MadelineProto->[messages_startBot](messages_startBot.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'start_param' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)<a name="messages_startBot"></a>  

$MadelineProto->[messages_toggleChatAdmins](messages_toggleChatAdmins.md)(\['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)<a name="messages_toggleChatAdmins"></a>  

$MadelineProto->[messages_uninstallStickerSet](messages_uninstallStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$Bool](../types/Bool.md)<a name="messages_uninstallStickerSet"></a>  

***
<br><br>$MadelineProto->[photos_deletePhotos](photos_deletePhotos.md)(\['id' => \[[InputPhoto](../types/InputPhoto.md)\], \]) == [$Vector\_of\_long](../types/long.md)<a name="photos_deletePhotos"></a>  

$MadelineProto->[photos_getUserPhotos](photos_getUserPhotos.md)(\['user_id' => [InputUser](../types/InputUser.md), 'offset' => [int](../types/int.md), 'max_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$photos\_Photos](../types/photos_Photos.md)<a name="photos_getUserPhotos"></a>  

$MadelineProto->[photos_updateProfilePhoto](photos_updateProfilePhoto.md)(\['id' => [InputPhoto](../types/InputPhoto.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \]) == [$UserProfilePhoto](../types/UserProfilePhoto.md)<a name="photos_updateProfilePhoto"></a>  

$MadelineProto->[photos_uploadProfilePhoto](photos_uploadProfilePhoto.md)(\['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \]) == [$photos\_Photo](../types/photos_Photo.md)<a name="photos_uploadProfilePhoto"></a>  

***
<br><br>$MadelineProto->[updates_getChannelDifference](updates_getChannelDifference.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelMessagesFilter](../types/ChannelMessagesFilter.md), 'pts' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$updates\_ChannelDifference](../types/updates_ChannelDifference.md)<a name="updates_getChannelDifference"></a>  

$MadelineProto->[updates_getDifference](updates_getDifference.md)(\['pts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'qts' => [int](../types/int.md), \]) == [$updates\_Difference](../types/updates_Difference.md)<a name="updates_getDifference"></a>  

$MadelineProto->[updates_getState](updates_getState.md)(\[\]) == [$updates\_State](../types/updates_State.md)<a name="updates_getState"></a>  

***
<br><br>$MadelineProto->[upload_getFile](upload_getFile.md)(\['location' => [InputFileLocation](../types/InputFileLocation.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$upload\_File](../types/upload_File.md)<a name="upload_getFile"></a>  

$MadelineProto->[upload_saveBigFilePart](upload_saveBigFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'file_total_parts' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)<a name="upload_saveBigFilePart"></a>  

$MadelineProto->[upload_saveFilePart](upload_saveFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)<a name="upload_saveFilePart"></a>  

***
<br><br>$MadelineProto->[users_getFullUser](users_getFullUser.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$UserFull](../types/UserFull.md)<a name="users_getFullUser"></a>  

$MadelineProto->[users_getUsers](users_getUsers.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Vector\_of\_User](../types/User.md)<a name="users_getUsers"></a>  

