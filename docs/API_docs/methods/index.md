# Methods  

$MadelineProto->[account->changePhone](account_changePhone.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[account->checkUsername](account_checkUsername.md)(\['username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->confirmPhone](account_confirmPhone.md)(\['phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->deleteAccount](account_deleteAccount.md)(\['reason' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->getAccountTTL](account_getAccountTTL.md)(\[\]) == [$AccountDaysTTL](../types/AccountDaysTTL.md)  

$MadelineProto->[account->getAuthorizations](account_getAuthorizations.md)(\[\]) == [$account\_Authorizations](../types/account_Authorizations.md)  

$MadelineProto->[account->getNotifySettings](account_getNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), \]) == [$PeerNotifySettings](../types/PeerNotifySettings.md)  

$MadelineProto->[account->getPassword](account_getPassword.md)(\[\]) == [$account\_Password](../types/account_Password.md)  

$MadelineProto->[account->getPasswordSettings](account_getPasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), \]) == [$account\_PasswordSettings](../types/account_PasswordSettings.md)  

$MadelineProto->[account->getPrivacy](account_getPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)  

$MadelineProto->[account->getWallPapers](account_getWallPapers.md)(\[\]) == [$Vector\_of\_WallPaper](../types/WallPaper.md)  

$MadelineProto->[account->registerDevice](account_registerDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->reportPeer](account_reportPeer.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'reason' => [ReportReason](../types/ReportReason.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->resetAuthorization](account_resetAuthorization.md)(\['hash' => [long](../types/long.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->resetNotifySettings](account_resetNotifySettings.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->sendChangePhoneCode](account_sendChangePhoneCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'phone_number' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[account->sendConfirmPhoneCode](account_sendConfirmPhoneCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'hash' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[account->setAccountTTL](account_setAccountTTL.md)(\['ttl' => [AccountDaysTTL](../types/AccountDaysTTL.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->setPrivacy](account_setPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), 'rules' => \[[InputPrivacyRule](../types/InputPrivacyRule.md)\], \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)  

$MadelineProto->[account->unregisterDevice](account_unregisterDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->updateDeviceLocked](account_updateDeviceLocked.md)(\['period' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->updateNotifySettings](account_updateNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), 'settings' => [InputPeerNotifySettings](../types/InputPeerNotifySettings.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->updatePasswordSettings](account_updatePasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), 'new_settings' => [account\_PasswordInputSettings](../types/account_PasswordInputSettings.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->updateProfile](account_updateProfile.md)(\['first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[account->updateStatus](account_updateStatus.md)(\['offline' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account->updateUsername](account_updateUsername.md)(\['username' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[auth->bindTempAuthKey](auth_bindTempAuthKey.md)(\['perm_auth_key_id' => [long](../types/long.md), 'nonce' => [long](../types/long.md), 'expires_at' => [int](../types/int.md), 'encrypted_message' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->cancelCode](auth_cancelCode.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->checkPassword](auth_checkPassword.md)(\['password_hash' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth->checkPhone](auth_checkPhone.md)(\['phone_number' => [string](../types/string.md), \]) == [$auth\_CheckedPhone](../types/auth_CheckedPhone.md)  

$MadelineProto->[auth->dropTempAuthKeys](auth_dropTempAuthKeys.md)(\['except_auth_keys' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->exportAuthorization](auth_exportAuthorization.md)(\['dc_id' => [int](../types/int.md), \]) == [$auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)  

$MadelineProto->[auth->importAuthorization](auth_importAuthorization.md)(\['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth->importBotAuthorization](auth_importBotAuthorization.md)(\['api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), 'bot_auth_token' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth->logOut](auth_logOut.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->recoverPassword](auth_recoverPassword.md)(\['code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth->requestPasswordRecovery](auth_requestPasswordRecovery.md)(\[\]) == [$auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)  

$MadelineProto->[auth->resendCode](auth_resendCode.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[auth->resetAuthorizations](auth_resetAuthorizations.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->sendCode](auth_sendCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'phone_number' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), 'api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[auth->sendInvites](auth_sendInvites.md)(\['phone_numbers' => \[[string](../types/string.md)\], 'message' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth->signIn](auth_signIn.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth->signUp](auth_signUp.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[channels->checkUsername](channels_checkUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels->createChannel](channels_createChannel.md)(\['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->deleteChannel](channels_deleteChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->deleteMessages](channels_deleteMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[channels->deleteUserHistory](channels_deleteUserHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)  

$MadelineProto->[channels->editAbout](channels_editAbout.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'about' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels->editAdmin](channels_editAdmin.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'role' => [ChannelParticipantRole](../types/ChannelParticipantRole.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->editPhoto](channels_editPhoto.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->editTitle](channels_editTitle.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->exportInvite](channels_exportInvite.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)  

$MadelineProto->[channels->exportMessageLink](channels_exportMessageLink.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$ExportedMessageLink](../types/ExportedMessageLink.md)  

$MadelineProto->[channels->getAdminedPublicChannels](channels_getAdminedPublicChannels.md)(\[\]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[channels->getChannels](channels_getChannels.md)(\['id' => \[[InputChannel](../types/InputChannel.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[channels->getFullChannel](channels_getFullChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)  

$MadelineProto->[channels->getMessages](channels_getMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[channels->getParticipant](channels_getParticipant.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)  

$MadelineProto->[channels->getParticipants](channels_getParticipants.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)  

$MadelineProto->[channels->inviteToChannel](channels_inviteToChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'users' => \[[InputUser](../types/InputUser.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->joinChannel](channels_joinChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->kickFromChannel](channels_kickFromChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'kicked' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->leaveChannel](channels_leaveChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->readHistory](channels_readHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'max_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels->reportSpam](channels_reportSpam.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'id' => \[[int](../types/int.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels->toggleInvites](channels_toggleInvites.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->toggleSignatures](channels_toggleSignatures.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->updatePinnedMessage](channels_updatePinnedMessage.md)(\['silent' => [Bool](../types/Bool.md), 'channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels->updateUsername](channels_updateUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts->block](contacts_block.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts->deleteContact](contacts_deleteContact.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$contacts\_Link](../types/contacts_Link.md)  

$MadelineProto->[contacts->deleteContacts](contacts_deleteContacts.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts->exportCard](contacts_exportCard.md)(\[\]) == [$Vector\_of\_int](../types/int.md)  

$MadelineProto->[contacts->getBlocked](contacts_getBlocked.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Blocked](../types/contacts_Blocked.md)  

$MadelineProto->[contacts->getContacts](contacts_getContacts.md)(\['hash' => [string](../types/string.md), \]) == [$contacts\_Contacts](../types/contacts_Contacts.md)  

$MadelineProto->[contacts->getStatuses](contacts_getStatuses.md)(\[\]) == [$Vector\_of\_ContactStatus](../types/ContactStatus.md)  

$MadelineProto->[contacts->getTopPeers](contacts_getTopPeers.md)(\['correspondents' => [Bool](../types/Bool.md), 'bots_pm' => [Bool](../types/Bool.md), 'bots_inline' => [Bool](../types/Bool.md), 'groups' => [Bool](../types/Bool.md), 'channels' => [Bool](../types/Bool.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'hash' => [int](../types/int.md), \]) == [$contacts\_TopPeers](../types/contacts_TopPeers.md)  

$MadelineProto->[contacts->importCard](contacts_importCard.md)(\['export_card' => \[[int](../types/int.md)\], \]) == [$User](../types/User.md)  

$MadelineProto->[contacts->importContacts](contacts_importContacts.md)(\['contacts' => \[[InputContact](../types/InputContact.md)\], 'replace' => [Bool](../types/Bool.md), \]) == [$contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)  

$MadelineProto->[contacts->resetTopPeerRating](contacts_resetTopPeerRating.md)(\['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts->resolveUsername](contacts_resolveUsername.md)(\['username' => [string](../types/string.md), \]) == [$contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)  

$MadelineProto->[contacts->search](contacts_search.md)(\['q' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Found](../types/contacts_Found.md)  

$MadelineProto->[contacts->unblock](contacts_unblock.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[help->getAppChangelog](help_getAppChangelog.md)(\[\]) == [$help\_AppChangelog](../types/help_AppChangelog.md)  

$MadelineProto->[help->getAppUpdate](help_getAppUpdate.md)(\[\]) == [$help\_AppUpdate](../types/help_AppUpdate.md)  

$MadelineProto->[help->getConfig](help_getConfig.md)(\[\]) == [$Config](../types/Config.md)  

$MadelineProto->[help->getInviteText](help_getInviteText.md)(\[\]) == [$help\_InviteText](../types/help_InviteText.md)  

$MadelineProto->[help->getNearestDc](help_getNearestDc.md)(\[\]) == [$NearestDc](../types/NearestDc.md)  

$MadelineProto->[help->getSupport](help_getSupport.md)(\[\]) == [$help\_Support](../types/help_Support.md)  

$MadelineProto->[help->getTermsOfService](help_getTermsOfService.md)(\[\]) == [$help\_TermsOfService](../types/help_TermsOfService.md)  

$MadelineProto->[help->saveAppLog](help_saveAppLog.md)(\['events' => \[[InputAppEvent](../types/InputAppEvent.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[initConnection](initConnection.md)(\['api_id' => [int](../types/int.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeAfterMsg](invokeAfterMsg.md)(\['msg_id' => [long](../types/long.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeAfterMsgs](invokeAfterMsgs.md)(\['msg_ids' => \[[long](../types/long.md)\], 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeWithLayer](invokeWithLayer.md)(\['layer' => [int](../types/int.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeWithoutUpdates](invokeWithoutUpdates.md)(\['query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[messages->acceptEncryption](messages_acceptEncryption.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'g_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)  

$MadelineProto->[messages->addChatUser](messages_addChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'fwd_limit' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->checkChatInvite](messages_checkChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$ChatInvite](../types/ChatInvite.md)  

$MadelineProto->[messages->clearRecentStickers](messages_clearRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->createChat](messages_createChat.md)(\['users' => \[[InputUser](../types/InputUser.md)\], 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->deleteChatUser](messages_deleteChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->deleteHistory](messages_deleteHistory.md)(\['just_clear' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)  

$MadelineProto->[messages->deleteMessages](messages_deleteMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages->discardEncryption](messages_discardEncryption.md)(\['chat_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->editChatAdmin](messages_editChatAdmin.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'is_admin' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->editChatPhoto](messages_editChatPhoto.md)(\['chat_id' => [int](../types/int.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->editChatTitle](messages_editChatTitle.md)(\['chat_id' => [int](../types/int.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->editInlineBotMessage](messages_editInlineBotMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'message' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->editMessage](messages_editMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->exportChatInvite](messages_exportChatInvite.md)(\['chat_id' => [int](../types/int.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)  

$MadelineProto->[messages->forwardMessage](messages_forwardMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->forwardMessages](messages_forwardMessages.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'with_my_score' => [Bool](../types/Bool.md), 'from_peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'random_id' => \[[long](../types/long.md)\], 'to_peer' => [InputPeer](../types/InputPeer.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->getAllDrafts](messages_getAllDrafts.md)(\[\]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->getAllStickers](messages_getAllStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages_AllStickers.md)  

$MadelineProto->[messages->getArchivedStickers](messages_getArchivedStickers.md)(\['masks' => [Bool](../types/Bool.md), 'offset_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$messages\_ArchivedStickers](../types/messages_ArchivedStickers.md)  

$MadelineProto->[messages->getAttachedStickers](messages_getAttachedStickers.md)(\['media' => [InputStickeredMedia](../types/InputStickeredMedia.md), \]) == [$Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)  

$MadelineProto->[messages->getBotCallbackAnswer](messages_getBotCallbackAnswer.md)(\['game' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'msg_id' => [int](../types/int.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md)  

$MadelineProto->[messages->getChats](messages_getChats.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[messages->getDhConfig](messages_getDhConfig.md)(\['version' => [int](../types/int.md), 'random_length' => [int](../types/int.md), \]) == [$messages\_DhConfig](../types/messages_DhConfig.md)  

$MadelineProto->[messages->getDialogs](messages_getDialogs.md)(\['offset_date' => [int](../types/int.md), 'offset_id' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Dialogs](../types/messages_Dialogs.md)  

$MadelineProto->[messages->getDocumentByHash](messages_getDocumentByHash.md)(\['sha256' => [bytes](../types/bytes.md), 'size' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), \]) == [$Document](../types/Document.md)  

$MadelineProto->[messages->getFeaturedStickers](messages_getFeaturedStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)  

$MadelineProto->[messages->getFullChat](messages_getFullChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)  

$MadelineProto->[messages->getGameHighScores](messages_getGameHighScores.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages_HighScores.md)  

$MadelineProto->[messages->getHistory](messages_getHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'offset_date' => [int](../types/int.md), 'add_offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'min_id' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages->getInlineBotResults](messages_getInlineBotResults.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \]) == [$messages\_BotResults](../types/messages_BotResults.md)  

$MadelineProto->[messages->getInlineGameHighScores](messages_getInlineGameHighScores.md)(\['id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages_HighScores.md)  

$MadelineProto->[messages->getMaskStickers](messages_getMaskStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages_AllStickers.md)  

$MadelineProto->[messages->getMessageEditData](messages_getMessageEditData.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), \]) == [$messages\_MessageEditData](../types/messages_MessageEditData.md)  

$MadelineProto->[messages->getMessages](messages_getMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages->getMessagesViews](messages_getMessagesViews.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'increment' => [Bool](../types/Bool.md), \]) == [$Vector\_of\_int](../types/int.md)  

$MadelineProto->[messages->getPeerDialogs](messages_getPeerDialogs.md)(\['peers' => \[[InputPeer](../types/InputPeer.md)\], \]) == [$messages\_PeerDialogs](../types/messages_PeerDialogs.md)  

$MadelineProto->[messages->getPeerSettings](messages_getPeerSettings.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$PeerSettings](../types/PeerSettings.md)  

$MadelineProto->[messages->getRecentStickers](messages_getRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), 'hash' => [int](../types/int.md), \]) == [$messages\_RecentStickers](../types/messages_RecentStickers.md)  

$MadelineProto->[messages->getSavedGifs](messages_getSavedGifs.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_SavedGifs](../types/messages_SavedGifs.md)  

$MadelineProto->[messages->getStickerSet](messages_getStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$messages\_StickerSet](../types/messages_StickerSet.md)  

$MadelineProto->[messages->getWebPagePreview](messages_getWebPagePreview.md)(\['message' => [string](../types/string.md), \]) == [$MessageMedia](../types/MessageMedia.md)  

$MadelineProto->[messages->hideReportSpam](messages_hideReportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->importChatInvite](messages_importChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->installStickerSet](messages_installStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'archived' => [Bool](../types/Bool.md), \]) == [$messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)  

$MadelineProto->[messages->migrateChat](messages_migrateChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->readEncryptedHistory](messages_readEncryptedHistory.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'max_date' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->readFeaturedStickers](messages_readFeaturedStickers.md)(\['id' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->readHistory](messages_readHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages->readMessageContents](messages_readMessageContents.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages->receivedMessages](messages_receivedMessages.md)(\['max_id' => [int](../types/int.md), \]) == [$Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)  

$MadelineProto->[messages->receivedQueue](messages_receivedQueue.md)(\['max_qts' => [int](../types/int.md), \]) == [$Vector\_of\_long](../types/long.md)  

$MadelineProto->[messages->reorderStickerSets](messages_reorderStickerSets.md)(\['masks' => [Bool](../types/Bool.md), 'order' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->reportSpam](messages_reportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->requestEncryption](messages_requestEncryption.md)(\['user_id' => [InputUser](../types/InputUser.md), 'random_id' => [int](../types/int.md), 'g_a' => [bytes](../types/bytes.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)  

$MadelineProto->[messages->saveDraft](messages_saveDraft.md)(\['no_webpage' => [Bool](../types/Bool.md), 'reply_to_msg_id' => [int](../types/int.md), 'peer' => [InputPeer](../types/InputPeer.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->saveGif](messages_saveGif.md)(\['id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->saveRecentSticker](messages_saveRecentSticker.md)(\['attached' => [Bool](../types/Bool.md), 'id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->search](messages_search.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min_date' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages->searchGifs](messages_searchGifs.md)(\['q' => [string](../types/string.md), 'offset' => [int](../types/int.md), \]) == [$messages\_FoundGifs](../types/messages_FoundGifs.md)  

$MadelineProto->[messages->searchGlobal](messages_searchGlobal.md)(\['q' => [string](../types/string.md), 'offset_date' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages->sendEncrypted](messages_sendEncrypted.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages->sendEncryptedFile](messages_sendEncryptedFile.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'file' => [InputEncryptedFile](../types/InputEncryptedFile.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages->sendEncryptedService](messages_sendEncryptedService.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages->sendInlineBotResult](messages_sendInlineBotResult.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), 'query_id' => [long](../types/long.md), 'id' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->sendMedia](messages_sendMedia.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'media' => [InputMedia](../types/InputMedia.md), 'random_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->sendMessage](messages_sendMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'random_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->setBotCallbackAnswer](messages_setBotCallbackAnswer.md)(\['alert' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->setEncryptedTyping](messages_setEncryptedTyping.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'typing' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->setGameScore](messages_setGameScore.md)(\['edit_message' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->setInlineBotResults](messages_setInlineBotResults.md)(\['gallery' => [Bool](../types/Bool.md), 'private' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'results' => \[[InputBotInlineResult](../types/InputBotInlineResult.md)\], 'cache_time' => [int](../types/int.md), 'next_offset' => [string](../types/string.md), 'switch_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->setInlineGameScore](messages_setInlineGameScore.md)(\['edit_message' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->setTyping](messages_setTyping.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages->startBot](messages_startBot.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'random_id' => [long](../types/long.md), 'start_param' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->toggleChatAdmins](messages_toggleChatAdmins.md)(\['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages->uninstallStickerSet](messages_uninstallStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[photos->deletePhotos](photos_deletePhotos.md)(\['id' => \[[InputPhoto](../types/InputPhoto.md)\], \]) == [$Vector\_of\_long](../types/long.md)  

$MadelineProto->[photos->getUserPhotos](photos_getUserPhotos.md)(\['user_id' => [InputUser](../types/InputUser.md), 'offset' => [int](../types/int.md), 'max_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$photos\_Photos](../types/photos_Photos.md)  

$MadelineProto->[photos->updateProfilePhoto](photos_updateProfilePhoto.md)(\['id' => [InputPhoto](../types/InputPhoto.md), \]) == [$UserProfilePhoto](../types/UserProfilePhoto.md)  

$MadelineProto->[photos->uploadProfilePhoto](photos_uploadProfilePhoto.md)(\['file' => [InputFile](../types/InputFile.md), \]) == [$photos\_Photo](../types/photos_Photo.md)  

$MadelineProto->[updates->getChannelDifference](updates_getChannelDifference.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelMessagesFilter](../types/ChannelMessagesFilter.md), 'pts' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$updates\_ChannelDifference](../types/updates_ChannelDifference.md)  

$MadelineProto->[updates->getDifference](updates_getDifference.md)(\['pts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'qts' => [int](../types/int.md), \]) == [$updates\_Difference](../types/updates_Difference.md)  

$MadelineProto->[updates->getState](updates_getState.md)(\[\]) == [$updates\_State](../types/updates_State.md)  

$MadelineProto->[upload->getFile](upload_getFile.md)(\['location' => [InputFileLocation](../types/InputFileLocation.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$upload\_File](../types/upload_File.md)  

$MadelineProto->[upload->saveBigFilePart](upload_saveBigFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'file_total_parts' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[upload->saveFilePart](upload_saveFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[users->getFullUser](users_getFullUser.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$UserFull](../types/UserFull.md)  

$MadelineProto->[users->getUsers](users_getUsers.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Vector\_of\_User](../types/User.md)  

