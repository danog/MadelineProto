# Methods  

$MadelineProto->[account\_changePhone](account.changePhone.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[account\_checkUsername](account.checkUsername.md)(\['username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_confirmPhone](account.confirmPhone.md)(\['phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_deleteAccount](account.deleteAccount.md)(\['reason' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_getAccountTTL](account.getAccountTTL.md)(\[\]) == [$AccountDaysTTL](../types/AccountDaysTTL.md)  

$MadelineProto->[account\_getAuthorizations](account.getAuthorizations.md)(\[\]) == [$account\_Authorizations](../types/account_Authorizations.md)  

$MadelineProto->[account\_getNotifySettings](account.getNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), \]) == [$PeerNotifySettings](../types/PeerNotifySettings.md)  

$MadelineProto->[account\_getPassword](account.getPassword.md)(\[\]) == [$account\_Password](../types/account_Password.md)  

$MadelineProto->[account\_getPasswordSettings](account.getPasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), \]) == [$account\_PasswordSettings](../types/account_PasswordSettings.md)  

$MadelineProto->[account\_getPrivacy](account.getPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)  

$MadelineProto->[account\_getWallPapers](account.getWallPapers.md)(\[\]) == [$Vector\_of\_WallPaper](../types/WallPaper.md)  

$MadelineProto->[account\_registerDevice](account.registerDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_reportPeer](account.reportPeer.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'reason' => [ReportReason](../types/ReportReason.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_resetAuthorization](account.resetAuthorization.md)(\['hash' => [long](../types/long.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_resetNotifySettings](account.resetNotifySettings.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_sendChangePhoneCode](account.sendChangePhoneCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'phone_number' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[account\_sendConfirmPhoneCode](account.sendConfirmPhoneCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'hash' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[account\_setAccountTTL](account.setAccountTTL.md)(\['ttl' => [AccountDaysTTL](../types/AccountDaysTTL.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_setPrivacy](account.setPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), 'rules' => \[[InputPrivacyRule](../types/InputPrivacyRule.md)\], \]) == [$account\_PrivacyRules](../types/account_PrivacyRules.md)  

$MadelineProto->[account\_unregisterDevice](account.unregisterDevice.md)(\['token_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_updateDeviceLocked](account.updateDeviceLocked.md)(\['period' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_updateNotifySettings](account.updateNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), 'settings' => [InputPeerNotifySettings](../types/InputPeerNotifySettings.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_updatePasswordSettings](account.updatePasswordSettings.md)(\['current_password_hash' => [bytes](../types/bytes.md), 'new_settings' => [account\_PasswordInputSettings](../types/account_PasswordInputSettings.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_updateProfile](account.updateProfile.md)(\['first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[account\_updateStatus](account.updateStatus.md)(\['offline' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[account\_updateUsername](account.updateUsername.md)(\['username' => [string](../types/string.md), \]) == [$User](../types/User.md)  

$MadelineProto->[auth\_bindTempAuthKey](auth.bindTempAuthKey.md)(\['perm_auth_key_id' => [long](../types/long.md), 'nonce' => [long](../types/long.md), 'expires_at' => [int](../types/int.md), 'encrypted_message' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_cancelCode](auth.cancelCode.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_checkPassword](auth.checkPassword.md)(\['password_hash' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth\_checkPhone](auth.checkPhone.md)(\['phone_number' => [string](../types/string.md), \]) == [$auth\_CheckedPhone](../types/auth_CheckedPhone.md)  

$MadelineProto->[auth\_dropTempAuthKeys](auth.dropTempAuthKeys.md)(\['except_auth_keys' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_exportAuthorization](auth.exportAuthorization.md)(\['dc_id' => [int](../types/int.md), \]) == [$auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)  

$MadelineProto->[auth\_importAuthorization](auth.importAuthorization.md)(\['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth\_importBotAuthorization](auth.importBotAuthorization.md)(\['api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), 'bot_auth_token' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth\_logOut](auth.logOut.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_recoverPassword](auth.recoverPassword.md)(\['code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth\_requestPasswordRecovery](auth.requestPasswordRecovery.md)(\[\]) == [$auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)  

$MadelineProto->[auth\_resendCode](auth.resendCode.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[auth\_resetAuthorizations](auth.resetAuthorizations.md)(\[\]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_sendCode](auth.sendCode.md)(\['allow_flashcall' => [Bool](../types/Bool.md), 'phone_number' => [string](../types/string.md), 'current_number' => [Bool](../types/Bool.md), 'api_id' => [int](../types/int.md), 'api_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth_SentCode.md)  

$MadelineProto->[auth\_sendInvites](auth.sendInvites.md)(\['phone_numbers' => \[[string](../types/string.md)\], 'message' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[auth\_signIn](auth.signIn.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[auth\_signUp](auth.signUp.md)(\['phone_number' => [string](../types/string.md), 'phone_code_hash' => [string](../types/string.md), 'phone_code' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth_Authorization.md)  

$MadelineProto->[channels\_checkUsername](channels.checkUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels\_createChannel](channels.createChannel.md)(\['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_deleteChannel](channels.deleteChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_deleteMessages](channels.deleteMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[channels\_deleteUserHistory](channels.deleteUserHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)  

$MadelineProto->[channels\_editAbout](channels.editAbout.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'about' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels\_editAdmin](channels.editAdmin.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'role' => [ChannelParticipantRole](../types/ChannelParticipantRole.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_editPhoto](channels.editPhoto.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_editTitle](channels.editTitle.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_exportInvite](channels.exportInvite.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)  

$MadelineProto->[channels\_exportMessageLink](channels.exportMessageLink.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$ExportedMessageLink](../types/ExportedMessageLink.md)  

$MadelineProto->[channels\_getAdminedPublicChannels](channels.getAdminedPublicChannels.md)(\[\]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[channels\_getChannels](channels.getChannels.md)(\['id' => \[[InputChannel](../types/InputChannel.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[channels\_getFullChannel](channels.getFullChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)  

$MadelineProto->[channels\_getMessages](channels.getMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[channels\_getParticipant](channels.getParticipant.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)  

$MadelineProto->[channels\_getParticipants](channels.getParticipants.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)  

$MadelineProto->[channels\_inviteToChannel](channels.inviteToChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'users' => \[[InputUser](../types/InputUser.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_joinChannel](channels.joinChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_kickFromChannel](channels.kickFromChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'kicked' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_leaveChannel](channels.leaveChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_readHistory](channels.readHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'max_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels\_reportSpam](channels.reportSpam.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user_id' => [InputUser](../types/InputUser.md), 'id' => \[[int](../types/int.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[channels\_toggleInvites](channels.toggleInvites.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_toggleSignatures](channels.toggleSignatures.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_updatePinnedMessage](channels.updatePinnedMessage.md)(\['silent' => [Bool](../types/Bool.md), 'channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[channels\_updateUsername](channels.updateUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts\_block](contacts.block.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts\_deleteContact](contacts.deleteContact.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$contacts\_Link](../types/contacts_Link.md)  

$MadelineProto->[contacts\_deleteContacts](contacts.deleteContacts.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts\_exportCard](contacts.exportCard.md)(\[\]) == [$Vector\_of\_int](../types/int.md)  

$MadelineProto->[contacts\_getBlocked](contacts.getBlocked.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Blocked](../types/contacts_Blocked.md)  

$MadelineProto->[contacts\_getContacts](contacts.getContacts.md)(\['hash' => [string](../types/string.md), \]) == [$contacts\_Contacts](../types/contacts_Contacts.md)  

$MadelineProto->[contacts\_getStatuses](contacts.getStatuses.md)(\[\]) == [$Vector\_of\_ContactStatus](../types/ContactStatus.md)  

$MadelineProto->[contacts\_getTopPeers](contacts.getTopPeers.md)(\['correspondents' => [Bool](../types/Bool.md), 'bots_pm' => [Bool](../types/Bool.md), 'bots_inline' => [Bool](../types/Bool.md), 'groups' => [Bool](../types/Bool.md), 'channels' => [Bool](../types/Bool.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'hash' => [int](../types/int.md), \]) == [$contacts\_TopPeers](../types/contacts_TopPeers.md)  

$MadelineProto->[contacts\_importCard](contacts.importCard.md)(\['export_card' => \[[int](../types/int.md)\], \]) == [$User](../types/User.md)  

$MadelineProto->[contacts\_importContacts](contacts.importContacts.md)(\['contacts' => \[[InputContact](../types/InputContact.md)\], 'replace' => [Bool](../types/Bool.md), \]) == [$contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)  

$MadelineProto->[contacts\_resetTopPeerRating](contacts.resetTopPeerRating.md)(\['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[contacts\_resolveUsername](contacts.resolveUsername.md)(\['username' => [string](../types/string.md), \]) == [$contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)  

$MadelineProto->[contacts\_search](contacts.search.md)(\['q' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Found](../types/contacts_Found.md)  

$MadelineProto->[contacts\_unblock](contacts.unblock.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[help\_getAppChangelog](help.getAppChangelog.md)(\[\]) == [$help\_AppChangelog](../types/help_AppChangelog.md)  

$MadelineProto->[help\_getAppUpdate](help.getAppUpdate.md)(\[\]) == [$help\_AppUpdate](../types/help_AppUpdate.md)  

$MadelineProto->[help\_getConfig](help.getConfig.md)(\[\]) == [$Config](../types/Config.md)  

$MadelineProto->[help\_getInviteText](help.getInviteText.md)(\[\]) == [$help\_InviteText](../types/help_InviteText.md)  

$MadelineProto->[help\_getNearestDc](help.getNearestDc.md)(\[\]) == [$NearestDc](../types/NearestDc.md)  

$MadelineProto->[help\_getSupport](help.getSupport.md)(\[\]) == [$help\_Support](../types/help_Support.md)  

$MadelineProto->[help\_getTermsOfService](help.getTermsOfService.md)(\[\]) == [$help\_TermsOfService](../types/help_TermsOfService.md)  

$MadelineProto->[help\_saveAppLog](help.saveAppLog.md)(\['events' => \[[InputAppEvent](../types/InputAppEvent.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[initConnection](initConnection.md)(\['api_id' => [int](../types/int.md), 'device_model' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'lang_code' => [string](../types/string.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeAfterMsg](invokeAfterMsg.md)(\['msg_id' => [long](../types/long.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeAfterMsgs](invokeAfterMsgs.md)(\['msg_ids' => \[[long](../types/long.md)\], 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeWithLayer](invokeWithLayer.md)(\['layer' => [int](../types/int.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[invokeWithoutUpdates](invokeWithoutUpdates.md)(\['query' => [!X](../types/!X.md), \]) == [$X](../types/X.md)  

$MadelineProto->[messages\_acceptEncryption](messages.acceptEncryption.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'g_b' => [bytes](../types/bytes.md), 'key_fingerprint' => [long](../types/long.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)  

$MadelineProto->[messages\_addChatUser](messages.addChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'fwd_limit' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_checkChatInvite](messages.checkChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$ChatInvite](../types/ChatInvite.md)  

$MadelineProto->[messages\_clearRecentStickers](messages.clearRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_createChat](messages.createChat.md)(\['users' => \[[InputUser](../types/InputUser.md)\], 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_deleteChatUser](messages.deleteChatUser.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_deleteHistory](messages.deleteHistory.md)(\['just_clear' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedHistory](../types/messages_AffectedHistory.md)  

$MadelineProto->[messages\_deleteMessages](messages.deleteMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages\_discardEncryption](messages.discardEncryption.md)(\['chat_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_editChatAdmin](messages.editChatAdmin.md)(\['chat_id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'is_admin' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_editChatPhoto](messages.editChatPhoto.md)(\['chat_id' => [int](../types/int.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_editChatTitle](messages.editChatTitle.md)(\['chat_id' => [int](../types/int.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_editInlineBotMessage](messages.editInlineBotMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'message' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_editMessage](messages.editMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_exportChatInvite](messages.exportChatInvite.md)(\['chat_id' => [int](../types/int.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md)  

$MadelineProto->[messages\_forwardMessage](messages.forwardMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_forwardMessages](messages.forwardMessages.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'with_my_score' => [Bool](../types/Bool.md), 'from_peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'random_id' => \[[long](../types/long.md)\], 'to_peer' => [InputPeer](../types/InputPeer.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_getAllDrafts](messages.getAllDrafts.md)(\[\]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_getAllStickers](messages.getAllStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages_AllStickers.md)  

$MadelineProto->[messages\_getArchivedStickers](messages.getArchivedStickers.md)(\['masks' => [Bool](../types/Bool.md), 'offset_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$messages\_ArchivedStickers](../types/messages_ArchivedStickers.md)  

$MadelineProto->[messages\_getAttachedStickers](messages.getAttachedStickers.md)(\['media' => [InputStickeredMedia](../types/InputStickeredMedia.md), \]) == [$Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)  

$MadelineProto->[messages\_getBotCallbackAnswer](messages.getBotCallbackAnswer.md)(\['game' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'msg_id' => [int](../types/int.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md)  

$MadelineProto->[messages\_getChats](messages.getChats.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Chats](../types/messages_Chats.md)  

$MadelineProto->[messages\_getDhConfig](messages.getDhConfig.md)(\['version' => [int](../types/int.md), 'random_length' => [int](../types/int.md), \]) == [$messages\_DhConfig](../types/messages_DhConfig.md)  

$MadelineProto->[messages\_getDialogs](messages.getDialogs.md)(\['offset_date' => [int](../types/int.md), 'offset_id' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Dialogs](../types/messages_Dialogs.md)  

$MadelineProto->[messages\_getDocumentByHash](messages.getDocumentByHash.md)(\['sha256' => [bytes](../types/bytes.md), 'size' => [int](../types/int.md), 'mime_type' => [string](../types/string.md), \]) == [$Document](../types/Document.md)  

$MadelineProto->[messages\_getFeaturedStickers](messages.getFeaturedStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)  

$MadelineProto->[messages\_getFullChat](messages.getFullChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$messages\_ChatFull](../types/messages_ChatFull.md)  

$MadelineProto->[messages\_getGameHighScores](messages.getGameHighScores.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages_HighScores.md)  

$MadelineProto->[messages\_getHistory](messages.getHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'offset_date' => [int](../types/int.md), 'add_offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'min_id' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages\_getInlineBotResults](messages.getInlineBotResults.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'geo_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \]) == [$messages\_BotResults](../types/messages_BotResults.md)  

$MadelineProto->[messages\_getInlineGameHighScores](messages.getInlineGameHighScores.md)(\['id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages_HighScores.md)  

$MadelineProto->[messages\_getMaskStickers](messages.getMaskStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages_AllStickers.md)  

$MadelineProto->[messages\_getMessageEditData](messages.getMessageEditData.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), \]) == [$messages\_MessageEditData](../types/messages_MessageEditData.md)  

$MadelineProto->[messages\_getMessages](messages.getMessages.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages\_getMessagesViews](messages.getMessagesViews.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => \[[int](../types/int.md)\], 'increment' => [Bool](../types/Bool.md), \]) == [$Vector\_of\_int](../types/int.md)  

$MadelineProto->[messages\_getPeerDialogs](messages.getPeerDialogs.md)(\['peers' => \[[InputPeer](../types/InputPeer.md)\], \]) == [$messages\_PeerDialogs](../types/messages_PeerDialogs.md)  

$MadelineProto->[messages\_getPeerSettings](messages.getPeerSettings.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$PeerSettings](../types/PeerSettings.md)  

$MadelineProto->[messages\_getRecentStickers](messages.getRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), 'hash' => [int](../types/int.md), \]) == [$messages\_RecentStickers](../types/messages_RecentStickers.md)  

$MadelineProto->[messages\_getSavedGifs](messages.getSavedGifs.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_SavedGifs](../types/messages_SavedGifs.md)  

$MadelineProto->[messages\_getStickerSet](messages.getStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$messages\_StickerSet](../types/messages_StickerSet.md)  

$MadelineProto->[messages\_getWebPagePreview](messages.getWebPagePreview.md)(\['message' => [string](../types/string.md), \]) == [$MessageMedia](../types/MessageMedia.md)  

$MadelineProto->[messages\_hideReportSpam](messages.hideReportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_importChatInvite](messages.importChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_installStickerSet](messages.installStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'archived' => [Bool](../types/Bool.md), \]) == [$messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)  

$MadelineProto->[messages\_migrateChat](messages.migrateChat.md)(\['chat_id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_readEncryptedHistory](messages.readEncryptedHistory.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'max_date' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_readFeaturedStickers](messages.readFeaturedStickers.md)(\['id' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_readHistory](messages.readHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max_id' => [int](../types/int.md), \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages\_readMessageContents](messages.readMessageContents.md)(\['id' => \[[int](../types/int.md)\], \]) == [$messages\_AffectedMessages](../types/messages_AffectedMessages.md)  

$MadelineProto->[messages\_receivedMessages](messages.receivedMessages.md)(\['max_id' => [int](../types/int.md), \]) == [$Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)  

$MadelineProto->[messages\_receivedQueue](messages.receivedQueue.md)(\['max_qts' => [int](../types/int.md), \]) == [$Vector\_of\_long](../types/long.md)  

$MadelineProto->[messages\_reorderStickerSets](messages.reorderStickerSets.md)(\['masks' => [Bool](../types/Bool.md), 'order' => \[[long](../types/long.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_reportSpam](messages.reportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_requestEncryption](messages.requestEncryption.md)(\['user_id' => [InputUser](../types/InputUser.md), 'random_id' => [int](../types/int.md), 'g_a' => [bytes](../types/bytes.md), \]) == [$EncryptedChat](../types/EncryptedChat.md)  

$MadelineProto->[messages\_saveDraft](messages.saveDraft.md)(\['no_webpage' => [Bool](../types/Bool.md), 'reply_to_msg_id' => [int](../types/int.md), 'peer' => [InputPeer](../types/InputPeer.md), 'message' => [string](../types/string.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_saveGif](messages.saveGif.md)(\['id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_saveRecentSticker](messages.saveRecentSticker.md)(\['attached' => [Bool](../types/Bool.md), 'id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_search](messages.search.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min_date' => [int](../types/int.md), 'max_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages\_searchGifs](messages.searchGifs.md)(\['q' => [string](../types/string.md), 'offset' => [int](../types/int.md), \]) == [$messages\_FoundGifs](../types/messages_FoundGifs.md)  

$MadelineProto->[messages\_searchGlobal](messages.searchGlobal.md)(\['q' => [string](../types/string.md), 'offset_date' => [int](../types/int.md), 'offset_peer' => [InputPeer](../types/InputPeer.md), 'offset_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages_Messages.md)  

$MadelineProto->[messages\_sendEncrypted](messages.sendEncrypted.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages\_sendEncryptedFile](messages.sendEncryptedFile.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'file' => [InputEncryptedFile](../types/InputEncryptedFile.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages\_sendEncryptedService](messages.sendEncryptedService.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)  

$MadelineProto->[messages\_sendInlineBotResult](messages.sendInlineBotResult.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'random_id' => [long](../types/long.md), 'query_id' => [long](../types/long.md), 'id' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_sendMedia](messages.sendMedia.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'media' => [InputMedia](../types/InputMedia.md), 'random_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_sendMessage](messages.sendMessage.md)(\['no_webpage' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply_to_msg_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'random_id' => [long](../types/long.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => \[[MessageEntity](../types/MessageEntity.md)\], \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_setBotCallbackAnswer](messages.setBotCallbackAnswer.md)(\['alert' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_setEncryptedTyping](messages.setEncryptedTyping.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'typing' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_setGameScore](messages.setGameScore.md)(\['edit_message' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_setInlineBotResults](messages.setInlineBotResults.md)(\['gallery' => [Bool](../types/Bool.md), 'private' => [Bool](../types/Bool.md), 'query_id' => [long](../types/long.md), 'results' => \[[InputBotInlineResult](../types/InputBotInlineResult.md)\], 'cache_time' => [int](../types/int.md), 'next_offset' => [string](../types/string.md), 'switch_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_setInlineGameScore](messages.setInlineGameScore.md)(\['edit_message' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_setTyping](messages.setTyping.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[messages\_startBot](messages.startBot.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'random_id' => [long](../types/long.md), 'start_param' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_toggleChatAdmins](messages.toggleChatAdmins.md)(\['chat_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md)  

$MadelineProto->[messages\_uninstallStickerSet](messages.uninstallStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[photos\_deletePhotos](photos.deletePhotos.md)(\['id' => \[[InputPhoto](../types/InputPhoto.md)\], \]) == [$Vector\_of\_long](../types/long.md)  

$MadelineProto->[photos\_getUserPhotos](photos.getUserPhotos.md)(\['user_id' => [InputUser](../types/InputUser.md), 'offset' => [int](../types/int.md), 'max_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$photos\_Photos](../types/photos_Photos.md)  

$MadelineProto->[photos\_updateProfilePhoto](photos.updateProfilePhoto.md)(\['id' => [InputPhoto](../types/InputPhoto.md), \]) == [$UserProfilePhoto](../types/UserProfilePhoto.md)  

$MadelineProto->[photos\_uploadProfilePhoto](photos.uploadProfilePhoto.md)(\['file' => [InputFile](../types/InputFile.md), \]) == [$photos\_Photo](../types/photos_Photo.md)  

$MadelineProto->[updates\_getChannelDifference](updates.getChannelDifference.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelMessagesFilter](../types/ChannelMessagesFilter.md), 'pts' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$updates\_ChannelDifference](../types/updates_ChannelDifference.md)  

$MadelineProto->[updates\_getDifference](updates.getDifference.md)(\['pts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'qts' => [int](../types/int.md), \]) == [$updates\_Difference](../types/updates_Difference.md)  

$MadelineProto->[updates\_getState](updates.getState.md)(\[\]) == [$updates\_State](../types/updates_State.md)  

$MadelineProto->[upload\_getFile](upload.getFile.md)(\['location' => [InputFileLocation](../types/InputFileLocation.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$upload\_File](../types/upload_File.md)  

$MadelineProto->[upload\_saveBigFilePart](upload.saveBigFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'file_total_parts' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[upload\_saveFilePart](upload.saveFilePart.md)(\['file_id' => [long](../types/long.md), 'file_part' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md)  

$MadelineProto->[users\_getFullUser](users.getFullUser.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$UserFull](../types/UserFull.md)  

$MadelineProto->[users\_getUsers](users.getUsers.md)(\['id' => \[[InputUser](../types/InputUser.md)\], \]) == [$Vector\_of\_User](../types/User.md)  

