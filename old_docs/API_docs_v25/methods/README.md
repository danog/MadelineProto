---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)


$MadelineProto->[logout](https://docs.madelineproto.xyz/logout.html)();

$MadelineProto->[phone_login](https://docs.madelineproto.xyz/phone_login.html)($number);

$MadelineProto->[complete_phone_login](https://docs.madelineproto.xyz/complete_phone_login.html)($code);

$MadelineProto->[complete_2FA_login](https://docs.madelineproto.xyz/complete_2FA_login.html)($password);

$MadelineProto->[bot_login](https://docs.madelineproto.xyz/complete_phone_login.html)($token);


$MadelineProto->[get_dialogs](https://docs.madelineproto.xyz/get_dialogs.html)();

$MadelineProto->[get_pwr_chat](https://docs.madelineproto.xyz/get_pwr_chat.html)($id);

$MadelineProto->[get_info](https://docs.madelineproto.xyz/get_info.html)($id);

$MadelineProto->[get_full_info](https://docs.madelineproto.xyz/get_full_info.html)($id);

$MadelineProto->[get_self](https://docs.madelineproto.xyz/get_self.html)();


***
<br><br>$MadelineProto->[account->changePhone](account_changePhone.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) === [$User](../types/User.md)<a name="account_changePhone"></a>  

$MadelineProto->[account->checkUsername](account_checkUsername.md)(\['username' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="account_checkUsername"></a>  

$MadelineProto->[account->deleteAccount](account_deleteAccount.md)(\['reason' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="account_deleteAccount"></a>  

$MadelineProto->[account->getAccountTTL](account_getAccountTTL.md)(\[\]) === [$AccountDaysTTL](../types/AccountDaysTTL.md)<a name="account_getAccountTTL"></a>  

$MadelineProto->[account->getNotifySettings](account_getNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), \]) === [$PeerNotifySettings](../types/PeerNotifySettings.md)<a name="account_getNotifySettings"></a>  

$MadelineProto->[account->getPassword](account_getPassword.md)(\[\]) === [$account\_Password](../types/account_Password.md)<a name="account_getPassword"></a>  

$MadelineProto->[account->getPrivacy](account_getPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), \]) === [$account\_PrivacyRules](../types/account_PrivacyRules.md)<a name="account_getPrivacy"></a>  

$MadelineProto->[account->getWallPapers](account_getWallPapers.md)(\[\]) === [$Vector\_of\_WallPaper](../types/WallPaper.md)<a name="account_getWallPapers"></a>  

$MadelineProto->[account->registerDevice](account_registerDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'app_sandbox' => [Bool](../types/Bool.md), 'lang_code' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="account_registerDevice"></a>  

$MadelineProto->[account->resetNotifySettings](account_resetNotifySettings.md)(\[\]) === [$Bool](../types/Bool.md)<a name="account_resetNotifySettings"></a>  

$MadelineProto->[account->sendChangePhoneCode](account_sendChangePhoneCode.md)(\['phone_number' => [string](../types/string.md), \]) === [$account\_SentChangePhoneCode](../types/account_SentChangePhoneCode.md)<a name="account_sendChangePhoneCode"></a>  

$MadelineProto->[account->setAccountTTL](account_setAccountTTL.md)(\['ttl' => [AccountDaysTTL](../types/AccountDaysTTL.md), \]) === [$Bool](../types/Bool.md)<a name="account_setAccountTTL"></a>  

$MadelineProto->[account->setPassword](account_setPassword.md)(\['current_password_hash' => [bytes](../types/bytes.md), 'new_salt' => [bytes](../types/bytes.md), 'new_password_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="account_setPassword"></a>  

$MadelineProto->[account->setPrivacy](account_setPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), 'rules' => \[[InputPrivacyRule](../types/InputPrivacyRule.md)\], \]) === [$account\_PrivacyRules](../types/account_PrivacyRules.md)<a name="account_setPrivacy"></a>  

$MadelineProto->[account->unregisterDevice](account_unregisterDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="account_unregisterDevice"></a>  

$MadelineProto->[account->updateDeviceLocked](account_updateDeviceLocked.md)(\['period' => [int](../types/int.md), \]) === [$Bool](../types/Bool.md)<a name="account_updateDeviceLocked"></a>  

$MadelineProto->[account->updateNotifySettings](account_updateNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), 'settings' => [InputPeerNotifySettings](../types/InputPeerNotifySettings.md), \]) === [$Bool](../types/Bool.md)<a name="account_updateNotifySettings"></a>  

$MadelineProto->[account->updateProfile](account_updateProfile.md)(\['first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) === [$User](../types/User.md)<a name="account_updateProfile"></a>  

$MadelineProto->[account->updateStatus](account_updateStatus.md)(\['offline' => [Bool](../types/Bool.md), \]) === [$Bool](../types/Bool.md)<a name="account_updateStatus"></a>  

$MadelineProto->[account->updateUsername](account_updateUsername.md)(\['username' => [string](../types/string.md), \]) === [$User](../types/User.md)<a name="account_updateUsername"></a>  

***
<br><br>$MadelineProto->[auth->bindTempAuthKey](auth_bindTempAuthKey.md)(\['perm_auth_key_id' => [long](../types/long.md), 'nonce' => [long](../types/long.md), 'expires_at' => [int](../types/int.md), 'encrypted_message' => [bytes](../types/bytes.md), \]) === [$Bool](../types/Bool.md)<a name="auth_bindTempAuthKey"></a>  

$MadelineProto->[auth->checkPassword](auth_checkPassword.md)(\['password_hash' => [bytes](../types/bytes.md), \]) === [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_checkPassword"></a>  

$MadelineProto->[auth->checkPhone](auth_checkPhone.md)(\['phone_number' => [string](../types/string.md), \]) === [$auth\_CheckedPhone](../types/auth_CheckedPhone.md)<a name="auth_checkPhone"></a>  

$MadelineProto->[auth->exportAuthorization](auth_exportAuthorization.md)(\['dc_id' => [int](../types/int.md), \]) === [$auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)<a name="auth_exportAuthorization"></a>  

$MadelineProto->[auth->importAuthorization](auth_importAuthorization.md)(\['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) === [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_importAuthorization"></a>  

$MadelineProto->[auth->logOut](auth_logOut.md)(\[\]) === [$Bool](../types/Bool.md)<a name="auth_logOut"></a>  

$MadelineProto->[auth->resetAuthorizations](auth_resetAuthorizations.md)(\[\]) === [$Bool](../types/Bool.md)<a name="auth_resetAuthorizations"></a>  

$MadelineProto->[auth->sendCall](auth_sendCall.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="auth_sendCall"></a>  

$MadelineProto->[auth->sendCode](auth_sendCode.md)(\['phone_number' => [string](../types/string.md), 'sms_type' => [int](../types/int.md), 'api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \]) === [$auth\_SentCode](../types/auth_SentCode.md)<a name="auth_sendCode"></a>  

$MadelineProto->[auth->sendInvites](auth_sendInvites.md)(\['phone_numbers' => \[[string](../types/string.md)\], 'message' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="auth_sendInvites"></a>  

$MadelineProto->[auth->sendSms](auth_sendSms.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) === [$Bool](../types/Bool.md)<a name="auth_sendSms"></a>  

$MadelineProto->[auth->signIn](auth_signIn.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) === [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_signIn"></a>  

$MadelineProto->[auth->signUp](auth_signUp.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) === [$auth\_Authorization](../types/auth_Authorization.md)<a name="auth_signUp"></a>  

***
<br><br>$MadelineProto->[contacts->block](contacts_block.md)(\['id' => [InputUser](../types/InputUser.md), \]) === [$Bool](../types/Bool.md)<a name="contacts_block"></a>  

$MadelineProto->[contacts->deleteContact](contacts_deleteContact.md)(\['id' => [InputUser](../types/InputUser.md), \]) === [$contacts\_Link](../types/contacts_Link.md)<a name="contacts_deleteContact"></a>  

$MadelineProto->[contacts->deleteContacts](contacts_deleteContacts.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) === [$Bool](../types/Bool.md)<a name="contacts_deleteContacts"></a>  

$MadelineProto->[contacts->exportCard](contacts_exportCard.md)(\[\]) === [$Vector\_of\_int](../types/int.md)<a name="contacts_exportCard"></a>  

$MadelineProto->[contacts->getBlocked](contacts_getBlocked.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$contacts\_Blocked](../types/contacts_Blocked.md)<a name="contacts_getBlocked"></a>  

$MadelineProto->[contacts->getContacts](contacts_getContacts.md)(\['hash' => [string](../types/string.md), \]) === [$contacts\_Contacts](../types/contacts_Contacts.md)<a name="contacts_getContacts"></a>  

$MadelineProto->[contacts->getStatuses](contacts_getStatuses.md)(\[\]) === [$Vector\_of\_ContactStatus](../types/ContactStatus.md)<a name="contacts_getStatuses"></a>  

$MadelineProto->[contacts->getSuggested](contacts_getSuggested.md)(\['limit' => [int](../types/int.md), \]) === [$contacts\_Suggested](../types/contacts_Suggested.md)<a name="contacts_getSuggested"></a>  

$MadelineProto->[contacts->importCard](contacts_importCard.md)(\['export_card' => \[[int](../types/int.md)\], \]) === [$User](../types/User.md)<a name="contacts_importCard"></a>  

$MadelineProto->[contacts->importContacts](contacts_importContacts.md)(\['contacts' => \[[InputContact](../types/InputContact.md)\], 'replace' => [Bool](../types/Bool.md), \]) === [$contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)<a name="contacts_importContacts"></a>  

$MadelineProto->[contacts->resolveUsername](contacts_resolveUsername.md)(\['username' => [string](../types/string.md), \]) === [$User](../types/User.md)<a name="contacts_resolveUsername"></a>  

$MadelineProto->[contacts->search](contacts_search.md)(\['q' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) === [$contacts\_Found](../types/contacts_Found.md)<a name="contacts_search"></a>  

$MadelineProto->[contacts->unblock](contacts_unblock.md)(\['id' => [InputUser](../types/InputUser.md), \]) === [$Bool](../types/Bool.md)<a name="contacts_unblock"></a>  

***
<br><br>$MadelineProto->[geochats->checkin](geochats_checkin.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_checkin"></a>  

$MadelineProto->[geochats->createGeoChat](geochats_createGeoChat.md)(\['title' => [string](../types/string.md), 'geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'address' => [string](../types/string.md), 'venue' => [string](../types/string.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_createGeoChat"></a>  

$MadelineProto->[geochats->editChatPhoto](geochats_editChatPhoto.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_editChatPhoto"></a>  

$MadelineProto->[geochats->editChatTitle](geochats_editChatTitle.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_editChatTitle"></a>  

$MadelineProto->[geochats->getFullChat](geochats_getFullChat.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), \]) === [$messages\_ChatFull](../types/messages_ChatFull.md)<a name="geochats_getFullChat"></a>  

$MadelineProto->[geochats->getHistory](geochats_getHistory.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$geochats\_Messages](../types/geochats_Messages.md)<a name="geochats_getHistory"></a>  

$MadelineProto->[geochats->getLocated](geochats_getLocated.md)(\['geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'radius' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$geochats\_Located](../types/geochats_Located.md)<a name="geochats_getLocated"></a>  

$MadelineProto->[geochats->getRecents](geochats_getRecents.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$geochats\_Messages](../types/geochats_Messages.md)<a name="geochats_getRecents"></a>  

$MadelineProto->[geochats->search](geochats_search.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min_date' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$geochats\_Messages](../types/geochats_Messages.md)<a name="geochats_search"></a>  

$MadelineProto->[geochats->sendMedia](geochats_sendMedia.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'media' => [InputMedia](../types/InputMedia.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_sendMedia"></a>  

$MadelineProto->[geochats->sendMessage](geochats_sendMessage.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'message' => [string](../types/string.md), \]) === [$geochats\_StatedMessage](../types/geochats_StatedMessage.md)<a name="geochats_sendMessage"></a>  

$MadelineProto->[geochats->setTyping](geochats_setTyping.md)(\['peer' => [InputGeoChat](../types/InputGeoChat.md), 'typing' => [Bool](../types/Bool.md), \]) === [$Bool](../types/Bool.md)<a name="geochats_setTyping"></a>  

***
<br><br>$MadelineProto->[help->getAppUpdate](help_getAppUpdate.md)(\['device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), \]) === [$help\_AppUpdate](../types/help_AppUpdate.md)<a name="help_getAppUpdate"></a>  

$MadelineProto->[help->getConfig](help_getConfig.md)(\[\]) === [$Config](../types/Config.md)<a name="help_getConfig"></a>  

$MadelineProto->[help->getInviteText](help_getInviteText.md)(\['lang_code' => [string](../types/string.md), \]) === [$help\_InviteText](../types/help_InviteText.md)<a name="help_getInviteText"></a>  

$MadelineProto->[help->getNearestDc](help_getNearestDc.md)(\[\]) === [$NearestDc](../types/NearestDc.md)<a name="help_getNearestDc"></a>  

$MadelineProto->[help->getSupport](help_getSupport.md)(\[\]) === [$help\_Support](../types/help_Support.md)<a name="help_getSupport"></a>  

$MadelineProto->[help->saveAppLog](help_saveAppLog.md)(\['events' => \[[InputAppEvent](../types/InputAppEvent.md)\], \]) === [$Bool](../types/Bool.md)<a name="help_saveAppLog"></a>  

***
<br><br>$MadelineProto->[initConnection](initConnection.md)(\['api_id' => [int](../types/int.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), 'query' => [!X](../types/!X.md), \]) === [$X](../types/X.md)<a name="initConnection"></a>  

***
<br><br>$MadelineProto->[invokeAfterMsg](invokeAfterMsg.md)(\['msg_id' => [long](../types/long.md), 'query' => [!X](../types/!X.md), \]) === [$X](../types/X.md)<a name="invokeAfterMsg"></a>  

***
<br><br>$MadelineProto->[invokeAfterMsgs](invokeAfterMsgs.md)(\['msg_ids' => \[[long](../types/long.md)\], 'query' => [!X](../types/!X.md), \]) === [$X](../types/X.md)<a name="invokeAfterMsgs"></a>  

***
<br><br>$MadelineProto->[invokeWithLayer](invokeWithLayer.md)(\['layer' => [int](../types/int.md), 'query' => [!X](../types/!X.md), \]) === [$X](../types/X.md)<a name="invokeWithLayer"></a>  

***
<br><br>$MadelineProto->[messages->acceptEncryption](messages_acceptEncryption.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'g_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \]) === [$EncryptedChat](../types/EncryptedChat.md)<a name="messages_acceptEncryption"></a>  

$MadelineProto->[messages->addChatUser](messages_addChatUser.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [InputUser](../types/InputUser.md), 'fwd_limit' => [int](../types/int.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_addChatUser"></a>  

$MadelineProto->[messages->createChat](messages_createChat.md)(\['users' => \[[InputUser](../types/InputUser.md)\], 'title' => [string](../types/string.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_createChat"></a>  

$MadelineProto->[messages->deleteChatUser](messages_deleteChatUser.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'user_id' => [InputUser](../types/InputUser.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_deleteChatUser"></a>  

$MadelineProto->[messages->deleteHistory](messages_deleteHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset' => [int](../types/int.md), \]) === [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)<a name="messages_deleteHistory"></a>  

$MadelineProto->[messages->deleteMessages](messages_deleteMessages.md)(\['id' => \[[int](../types/int.md)\], \]) === [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="messages_deleteMessages"></a>  

$MadelineProto->[messages->discardEncryption](messages_discardEncryption.md)(\['chat_id' => [int](../types/int.md), \]) === [$Bool](../types/Bool.md)<a name="messages_discardEncryption"></a>  

$MadelineProto->[messages->editChatPhoto](messages_editChatPhoto.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_editChatPhoto"></a>  

$MadelineProto->[messages->editChatTitle](messages_editChatTitle.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), 'title' => [string](../types/string.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_editChatTitle"></a>  

$MadelineProto->[messages->forwardMessage](messages_forwardMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_forwardMessage"></a>  

$MadelineProto->[messages->forwardMessages](messages_forwardMessages.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], \]) === [$messages\_StatedMessages](../types/messages_StatedMessages.md)<a name="messages_forwardMessages"></a>  

$MadelineProto->[messages->getAllStickers](messages_getAllStickers.md)(\['hash' => [string](../types/string.md), \]) === [$messages\_AllStickers](../types/messages_AllStickers.md)<a name="messages_getAllStickers"></a>  

$MadelineProto->[messages->getChats](messages_getChats.md)(\['id' => \[[int](../types/int.md)\], \]) === [$messages\_Chats](../types/messages_Chats.md)<a name="messages_getChats"></a>  

$MadelineProto->[messages->getDhConfig](messages_getDhConfig.md)(\['version' => [int](../types/int.md), 'random_length' => [int](../types/int.md), \]) === [$messages\_DhConfig](../types/messages_DhConfig.md)<a name="messages_getDhConfig"></a>  

$MadelineProto->[messages->getDialogs](messages_getDialogs.md)(\['offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$messages\_Dialogs](../types/messages_Dialogs.md)<a name="messages_getDialogs"></a>  

$MadelineProto->[messages->getFullChat](messages_getFullChat.md)(\['chat_id' => [InputPeer](../types/InputPeer.md), \]) === [$messages\_ChatFull](../types/messages_ChatFull.md)<a name="messages_getFullChat"></a>  

$MadelineProto->[messages->getHistory](messages_getHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$messages\_Messages](../types/messages_Messages.md)<a name="messages_getHistory"></a>  

$MadelineProto->[messages->getMessages](messages_getMessages.md)(\['id' => \[[int](../types/int.md)\], \]) === [$messages\_Messages](../types/messages_Messages.md)<a name="messages_getMessages"></a>  

$MadelineProto->[messages->getStickers](messages_getStickers.md)(\['emoticon' => [string](../types/string.md), 'hash' => [string](../types/string.md), \]) === [$messages\_Stickers](../types/messages_Stickers.md)<a name="messages_getStickers"></a>  

$MadelineProto->[messages->readEncryptedHistory](messages_readEncryptedHistory.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'max_date' => [int](../types/int.md), \]) === [$Bool](../types/Bool.md)<a name="messages_readEncryptedHistory"></a>  

$MadelineProto->[messages->readHistory](messages_readHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), 'offset' => [int](../types/int.md), \]) === [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)<a name="messages_readHistory"></a>  

$MadelineProto->[messages->readMessageContents](messages_readMessageContents.md)(\['id' => \[[int](../types/int.md)\], \]) === [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)<a name="messages_readMessageContents"></a>  

$MadelineProto->[messages->receivedMessages](messages_receivedMessages.md)(\['max_id' => [int](../types/int.md), \]) === [$Vector\_of\_int](../types/int.md)<a name="messages_receivedMessages"></a>  

$MadelineProto->[messages->receivedQueue](messages_receivedQueue.md)(\['max_qts' => [int](../types/int.md), \]) === [$Vector\_of\_long](../types/long.md)<a name="messages_receivedQueue"></a>  

$MadelineProto->[messages->requestEncryption](messages_requestEncryption.md)(\['user_id' => [InputUser](../types/InputUser.md), 'g_a' => [bytes](../types/bytes.md), \]) === [$EncryptedChat](../types/EncryptedChat.md)<a name="messages_requestEncryption"></a>  

$MadelineProto->[messages->search](messages_search.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min_date' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$messages\_Messages](../types/messages_Messages.md)<a name="messages_search"></a>  

$MadelineProto->[messages->sendBroadcast](messages_sendBroadcast.md)(\['contacts' => \[[InputUser](../types/InputUser.md)\], 'message' => [string](../types/string.md), 'media' => [InputMedia](../types/InputMedia.md), \]) === [$messages\_StatedMessages](../types/messages_StatedMessages.md)<a name="messages_sendBroadcast"></a>  

$MadelineProto->[messages->sendEncrypted](messages_sendEncrypted.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'message' => [DecryptedMessage](../types/DecryptedMessage.md), \]) === [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncrypted"></a>  

$MadelineProto->[messages->sendEncryptedFile](messages_sendEncryptedFile.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'message' => [DecryptedMessage](../types/DecryptedMessage.md), 'file' => [InputEncryptedFile](../types/InputEncryptedFile.md), \]) === [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncryptedFile"></a>  

$MadelineProto->[messages->sendEncryptedService](messages_sendEncryptedService.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'message' => [DecryptedMessage](../types/DecryptedMessage.md), \]) === [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)<a name="messages_sendEncryptedService"></a>  

$MadelineProto->[messages->sendMedia](messages_sendMedia.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'media' => [InputMedia](../types/InputMedia.md), \]) === [$messages\_StatedMessage](../types/messages_StatedMessage.md)<a name="messages_sendMedia"></a>  

$MadelineProto->[messages->sendMessage](messages_sendMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), \]) === [$messages\_SentMessage](../types/messages_SentMessage.md)<a name="messages_sendMessage"></a>  

$MadelineProto->[messages->setEncryptedTyping](messages_setEncryptedTyping.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'typing' => [Bool](../types/Bool.md), \]) === [$Bool](../types/Bool.md)<a name="messages_setEncryptedTyping"></a>  

$MadelineProto->[messages->setTyping](messages_setTyping.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) === [$Bool](../types/Bool.md)<a name="messages_setTyping"></a>  

***
<br><br>$MadelineProto->[photos->deletePhotos](photos_deletePhotos.md)(\['id' => \[[InputPhoto](../types/InputPhoto.md)\], \]) === [$Vector\_of\_long](../types/long.md)<a name="photos_deletePhotos"></a>  

$MadelineProto->[photos->getUserPhotos](photos_getUserPhotos.md)(\['user_id' => [InputUser](../types/InputUser.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$photos\_Photos](../types/photos_Photos.md)<a name="photos_getUserPhotos"></a>  

$MadelineProto->[photos->updateProfilePhoto](photos_updateProfilePhoto.md)(\['id' => [InputPhoto](../types/InputPhoto.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \]) === [$UserProfilePhoto](../types/UserProfilePhoto.md)<a name="photos_updateProfilePhoto"></a>  

$MadelineProto->[photos->uploadProfilePhoto](photos_uploadProfilePhoto.md)(\['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'crop' => [InputPhotoCrop](../types/InputPhotoCrop.md), \]) === [$photos\_Photo](../types/photos_Photo.md)<a name="photos_uploadProfilePhoto"></a>  

***
<br><br>$MadelineProto->[updates->getDifference](updates_getDifference.md)(\['pts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'qts' => [int](../types/int.md), \]) === [$updates\_Difference](../types/updates_Difference.md)<a name="updates_getDifference"></a>  

$MadelineProto->[updates->getState](updates_getState.md)(\[\]) === [$updates\_State](../types/updates_State.md)<a name="updates_getState"></a>  

***
<br><br>$MadelineProto->[upload->getFile](upload_getFile.md)(\['location' => [InputFileLocation](../types/InputFileLocation.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) === [$upload\_File](../types/upload_File.md)<a name="upload_getFile"></a>  

$MadelineProto->[upload->saveBigFilePart](upload_saveBigFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'file_total_parts' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) === [$Bool](../types/Bool.md)<a name="upload_saveBigFilePart"></a>  

$MadelineProto->[upload->saveFilePart](upload_saveFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) === [$Bool](../types/Bool.md)<a name="upload_saveFilePart"></a>  

***
<br><br>$MadelineProto->[users->getFullUser](users_getFullUser.md)(\['id' => [InputUser](../types/InputUser.md), \]) === [$UserFull](../types/UserFull.md)<a name="users_getFullUser"></a>  

$MadelineProto->[users->getUsers](users_getUsers.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) === [$Vector\_of\_User](../types/User.md)<a name="users_getUsers"></a>  

