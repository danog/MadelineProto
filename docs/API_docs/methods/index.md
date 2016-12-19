# Methods  

$MadelineProto->[account->changePhone](account.changePhone.md)(\['phone\_number' => [string](../types/string.md), 'phone\_code\_hash' => [string](../types/string.md), 'phone\_code' => [string](../types/string.md), \]) == [$User](../types/User.md);  

$MadelineProto->[account->checkUsername](account.checkUsername.md)(\['username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->confirmPhone](account.confirmPhone.md)(\['phone\_code\_hash' => [string](../types/string.md), 'phone\_code' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->deleteAccount](account.deleteAccount.md)(\['reason' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->getAccountTTL](account.getAccountTTL.md)() == [$AccountDaysTTL](../types/AccountDaysTTL.md);  

$MadelineProto->[account->getAuthorizations](account.getAuthorizations.md)() == [$account\_Authorizations](../types/account\_Authorizations.md);  

$MadelineProto->[account->getNotifySettings](account.getNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), \]) == [$PeerNotifySettings](../types/PeerNotifySettings.md);  

$MadelineProto->[account->getPassword](account.getPassword.md)() == [$account\_Password](../types/account\_Password.md);  

$MadelineProto->[account->getPasswordSettings](account.getPasswordSettings.md)(\['current\_password\_hash' => [bytes](../types/bytes.md), \]) == [$account\_PasswordSettings](../types/account\_PasswordSettings.md);  

$MadelineProto->[account->getPrivacy](account.getPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), \]) == [$account\_PrivacyRules](../types/account\_PrivacyRules.md);  

$MadelineProto->[account->getWallPapers](account.getWallPapers.md)() == [$Vector\_of\_WallPaper](../types/WallPaper.md);  

$MadelineProto->[account->registerDevice](account.registerDevice.md)(\['token\_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->reportPeer](account.reportPeer.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'reason' => [ReportReason](../types/ReportReason.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->resetAuthorization](account.resetAuthorization.md)(\['hash' => [long](../types/long.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->resetNotifySettings](account.resetNotifySettings.md)() == [$Bool](../types/Bool.md);  

$MadelineProto->[account->sendChangePhoneCode](account.sendChangePhoneCode.md)(\['allow\_flashcall' => [Bool](../types/Bool.md), 'phone\_number' => [string](../types/string.md), 'current\_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth\_SentCode.md);  

$MadelineProto->[account->sendConfirmPhoneCode](account.sendConfirmPhoneCode.md)(\['allow\_flashcall' => [Bool](../types/Bool.md), 'hash' => [string](../types/string.md), 'current\_number' => [Bool](../types/Bool.md), \]) == [$auth\_SentCode](../types/auth\_SentCode.md);  

$MadelineProto->[account->setAccountTTL](account.setAccountTTL.md)(\['ttl' => [AccountDaysTTL](../types/AccountDaysTTL.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->setPrivacy](account.setPrivacy.md)(\['key' => [InputPrivacyKey](../types/InputPrivacyKey.md), 'rules' => [[InputPrivacyRule](../types/InputPrivacyRule.md)], \]) == [$account\_PrivacyRules](../types/account\_PrivacyRules.md);  

$MadelineProto->[account->unregisterDevice](account.unregisterDevice.md)(\['token\_type' => [int](../types/int.md), 'token' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->updateDeviceLocked](account.updateDeviceLocked.md)(\['period' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->updateNotifySettings](account.updateNotifySettings.md)(\['peer' => [InputNotifyPeer](../types/InputNotifyPeer.md), 'settings' => [InputPeerNotifySettings](../types/InputPeerNotifySettings.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->updatePasswordSettings](account.updatePasswordSettings.md)(\['current\_password\_hash' => [bytes](../types/bytes.md), 'new\_settings' => [account\_PasswordInputSettings](../types/account\_PasswordInputSettings.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->updateProfile](account.updateProfile.md)(\['first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$User](../types/User.md);  

$MadelineProto->[account->updateStatus](account.updateStatus.md)(\['offline' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[account->updateUsername](account.updateUsername.md)(\['username' => [string](../types/string.md), \]) == [$User](../types/User.md);  

$MadelineProto->[auth->bindTempAuthKey](auth.bindTempAuthKey.md)(\['perm\_auth\_key\_id' => [long](../types/long.md), 'nonce' => [long](../types/long.md), 'expires\_at' => [int](../types/int.md), 'encrypted\_message' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->cancelCode](auth.cancelCode.md)(\['phone\_number' => [string](../types/string.md), 'phone\_code\_hash' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->checkPassword](auth.checkPassword.md)(\['password\_hash' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[auth->checkPhone](auth.checkPhone.md)(\['phone\_number' => [string](../types/string.md), \]) == [$auth\_CheckedPhone](../types/auth\_CheckedPhone.md);  

$MadelineProto->[auth->dropTempAuthKeys](auth.dropTempAuthKeys.md)(\['except\_auth\_keys' => [[long](../types/long.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->exportAuthorization](auth.exportAuthorization.md)(\['dc\_id' => [int](../types/int.md), \]) == [$auth\_ExportedAuthorization](../types/auth\_ExportedAuthorization.md);  

$MadelineProto->[auth->importAuthorization](auth.importAuthorization.md)(\['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[auth->importBotAuthorization](auth.importBotAuthorization.md)(\['api\_id' => [int](../types/int.md), 'api\_hash' => [string](../types/string.md), 'bot\_auth\_token' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[auth->logOut](auth.logOut.md)() == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->recoverPassword](auth.recoverPassword.md)(\['code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[auth->requestPasswordRecovery](auth.requestPasswordRecovery.md)() == [$auth\_PasswordRecovery](../types/auth\_PasswordRecovery.md);  

$MadelineProto->[auth->resendCode](auth.resendCode.md)(\['phone\_number' => [string](../types/string.md), 'phone\_code\_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth\_SentCode.md);  

$MadelineProto->[auth->resetAuthorizations](auth.resetAuthorizations.md)() == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->sendCode](auth.sendCode.md)(\['allow\_flashcall' => [Bool](../types/Bool.md), 'phone\_number' => [string](../types/string.md), 'current\_number' => [Bool](../types/Bool.md), 'api\_id' => [int](../types/int.md), 'api\_hash' => [string](../types/string.md), \]) == [$auth\_SentCode](../types/auth\_SentCode.md);  

$MadelineProto->[auth->sendInvites](auth.sendInvites.md)(\['phone\_numbers' => [[string](../types/string.md)], 'message' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[auth->signIn](auth.signIn.md)(\['phone\_number' => [string](../types/string.md), 'phone\_code\_hash' => [string](../types/string.md), 'phone\_code' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[auth->signUp](auth.signUp.md)(\['phone\_number' => [string](../types/string.md), 'phone\_code\_hash' => [string](../types/string.md), 'phone\_code' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), \]) == [$auth\_Authorization](../types/auth\_Authorization.md);  

$MadelineProto->[channels->checkUsername](channels.checkUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[channels->createChannel](channels.createChannel.md)(\['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'about' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->deleteChannel](channels.deleteChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->deleteMessages](channels.deleteMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => [[int](../types/int.md)], \]) == [$messages\_AffectedMessages](../types/messages\_AffectedMessages.md);  

$MadelineProto->[channels->deleteUserHistory](channels.deleteUserHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user\_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_AffectedHistory](../types/messages\_AffectedHistory.md);  

$MadelineProto->[channels->editAbout](channels.editAbout.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'about' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[channels->editAdmin](channels.editAdmin.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user\_id' => [InputUser](../types/InputUser.md), 'role' => [ChannelParticipantRole](../types/ChannelParticipantRole.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->editPhoto](channels.editPhoto.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->editTitle](channels.editTitle.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->exportInvite](channels.exportInvite.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md);  

$MadelineProto->[channels->exportMessageLink](channels.exportMessageLink.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$ExportedMessageLink](../types/ExportedMessageLink.md);  

$MadelineProto->[channels->getAdminedPublicChannels](channels.getAdminedPublicChannels.md)() == [$messages\_Chats](../types/messages\_Chats.md);  

$MadelineProto->[channels->getChannels](channels.getChannels.md)(\['id' => [[InputChannel](../types/InputChannel.md)], \]) == [$messages\_Chats](../types/messages\_Chats.md);  

$MadelineProto->[channels->getFullChannel](channels.getFullChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$messages\_ChatFull](../types/messages\_ChatFull.md);  

$MadelineProto->[channels->getMessages](channels.getMessages.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'id' => [[int](../types/int.md)], \]) == [$messages\_Messages](../types/messages\_Messages.md);  

$MadelineProto->[channels->getParticipant](channels.getParticipant.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user\_id' => [InputUser](../types/InputUser.md), \]) == [$channels\_ChannelParticipant](../types/channels\_ChannelParticipant.md);  

$MadelineProto->[channels->getParticipants](channels.getParticipants.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$channels\_ChannelParticipants](../types/channels\_ChannelParticipants.md);  

$MadelineProto->[channels->inviteToChannel](channels.inviteToChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'users' => [[InputUser](../types/InputUser.md)], \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->joinChannel](channels.joinChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->kickFromChannel](channels.kickFromChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user\_id' => [InputUser](../types/InputUser.md), 'kicked' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->leaveChannel](channels.leaveChannel.md)(\['channel' => [InputChannel](../types/InputChannel.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->readHistory](channels.readHistory.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'max\_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[channels->reportSpam](channels.reportSpam.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'user\_id' => [InputUser](../types/InputUser.md), 'id' => [[int](../types/int.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[channels->toggleInvites](channels.toggleInvites.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->toggleSignatures](channels.toggleSignatures.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->updatePinnedMessage](channels.updatePinnedMessage.md)(\['silent' => [Bool](../types/Bool.md), 'channel' => [InputChannel](../types/InputChannel.md), 'id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[channels->updateUsername](channels.updateUsername.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'username' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[contacts->block](contacts.block.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[contacts->deleteContact](contacts.deleteContact.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$contacts\_Link](../types/contacts\_Link.md);  

$MadelineProto->[contacts->deleteContacts](contacts.deleteContacts.md)(\['id' => [[InputUser](../types/InputUser.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[contacts->exportCard](contacts.exportCard.md)() == [$Vector\_of\_int](../types/int.md);  

$MadelineProto->[contacts->getBlocked](contacts.getBlocked.md)(\['offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Blocked](../types/contacts\_Blocked.md);  

$MadelineProto->[contacts->getContacts](contacts.getContacts.md)(\['hash' => [string](../types/string.md), \]) == [$contacts\_Contacts](../types/contacts\_Contacts.md);  

$MadelineProto->[contacts->getStatuses](contacts.getStatuses.md)() == [$Vector\_of\_ContactStatus](../types/ContactStatus.md);  

$MadelineProto->[contacts->getTopPeers](contacts.getTopPeers.md)(\['correspondents' => [Bool](../types/Bool.md), 'bots\_pm' => [Bool](../types/Bool.md), 'bots\_inline' => [Bool](../types/Bool.md), 'groups' => [Bool](../types/Bool.md), 'channels' => [Bool](../types/Bool.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'hash' => [int](../types/int.md), \]) == [$contacts\_TopPeers](../types/contacts\_TopPeers.md);  

$MadelineProto->[contacts->importCard](contacts.importCard.md)(\['export\_card' => [[int](../types/int.md)], \]) == [$User](../types/User.md);  

$MadelineProto->[contacts->importContacts](contacts.importContacts.md)(\['contacts' => [[InputContact](../types/InputContact.md)], 'replace' => [Bool](../types/Bool.md), \]) == [$contacts\_ImportedContacts](../types/contacts\_ImportedContacts.md);  

$MadelineProto->[contacts->resetTopPeerRating](contacts.resetTopPeerRating.md)(\['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[contacts->resolveUsername](contacts.resolveUsername.md)(\['username' => [string](../types/string.md), \]) == [$contacts\_ResolvedPeer](../types/contacts\_ResolvedPeer.md);  

$MadelineProto->[contacts->search](contacts.search.md)(\['q' => [string](../types/string.md), 'limit' => [int](../types/int.md), \]) == [$contacts\_Found](../types/contacts\_Found.md);  

$MadelineProto->[contacts->unblock](contacts.unblock.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[help->getAppChangelog](help.getAppChangelog.md)() == [$help\_AppChangelog](../types/help\_AppChangelog.md);  

$MadelineProto->[help->getAppUpdate](help.getAppUpdate.md)() == [$help\_AppUpdate](../types/help\_AppUpdate.md);  

$MadelineProto->[help->getConfig](help.getConfig.md)() == [$Config](../types/Config.md);  

$MadelineProto->[help->getInviteText](help.getInviteText.md)() == [$help\_InviteText](../types/help\_InviteText.md);  

$MadelineProto->[help->getNearestDc](help.getNearestDc.md)() == [$NearestDc](../types/NearestDc.md);  

$MadelineProto->[help->getSupport](help.getSupport.md)() == [$help\_Support](../types/help\_Support.md);  

$MadelineProto->[help->getTermsOfService](help.getTermsOfService.md)() == [$help\_TermsOfService](../types/help\_TermsOfService.md);  

$MadelineProto->[help->saveAppLog](help.saveAppLog.md)(\['events' => [[InputAppEvent](../types/InputAppEvent.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[initConnection](initConnection.md)(\['api\_id' => [int](../types/int.md), 'device\_model' => [string](../types/string.md), 'system\_version' => [string](../types/string.md), 'app\_version' => [string](../types/string.md), 'lang\_code' => [string](../types/string.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md);  

$MadelineProto->[invokeAfterMsg](invokeAfterMsg.md)(\['msg\_id' => [long](../types/long.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md);  

$MadelineProto->[invokeAfterMsgs](invokeAfterMsgs.md)(\['msg\_ids' => [[long](../types/long.md)], 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md);  

$MadelineProto->[invokeWithLayer](invokeWithLayer.md)(\['layer' => [int](../types/int.md), 'query' => [!X](../types/!X.md), \]) == [$X](../types/X.md);  

$MadelineProto->[invokeWithoutUpdates](invokeWithoutUpdates.md)(\['query' => [!X](../types/!X.md), \]) == [$X](../types/X.md);  

$MadelineProto->[messages->acceptEncryption](messages.acceptEncryption.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'g\_b' => [bytes](../types/bytes.md), 'key\_fingerprint' => [long](../types/long.md), \]) == [$EncryptedChat](../types/EncryptedChat.md);  

$MadelineProto->[messages->addChatUser](messages.addChatUser.md)(\['chat\_id' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), 'fwd\_limit' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->checkChatInvite](messages.checkChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$ChatInvite](../types/ChatInvite.md);  

$MadelineProto->[messages->clearRecentStickers](messages.clearRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->createChat](messages.createChat.md)(\['users' => [[InputUser](../types/InputUser.md)], 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->deleteChatUser](messages.deleteChatUser.md)(\['chat\_id' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->deleteHistory](messages.deleteHistory.md)(\['just\_clear' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'max\_id' => [int](../types/int.md), \]) == [$messages\_AffectedHistory](../types/messages\_AffectedHistory.md);  

$MadelineProto->[messages->deleteMessages](messages.deleteMessages.md)(\['id' => [[int](../types/int.md)], \]) == [$messages\_AffectedMessages](../types/messages\_AffectedMessages.md);  

$MadelineProto->[messages->discardEncryption](messages.discardEncryption.md)(\['chat\_id' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->editChatAdmin](messages.editChatAdmin.md)(\['chat\_id' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), 'is\_admin' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->editChatPhoto](messages.editChatPhoto.md)(\['chat\_id' => [int](../types/int.md), 'photo' => [InputChatPhoto](../types/InputChatPhoto.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->editChatTitle](messages.editChatTitle.md)(\['chat\_id' => [int](../types/int.md), 'title' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->editInlineBotMessage](messages.editInlineBotMessage.md)(\['no\_webpage' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'message' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->editMessage](messages.editMessage.md)(\['no\_webpage' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->exportChatInvite](messages.exportChatInvite.md)(\['chat\_id' => [int](../types/int.md), \]) == [$ExportedChatInvite](../types/ExportedChatInvite.md);  

$MadelineProto->[messages->forwardMessage](messages.forwardMessage.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'random\_id' => [long](../types/long.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->forwardMessages](messages.forwardMessages.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'with\_my\_score' => [Bool](../types/Bool.md), 'from\_peer' => [InputPeer](../types/InputPeer.md), 'id' => [[int](../types/int.md)], 'random\_id' => [[long](../types/long.md)], 'to\_peer' => [InputPeer](../types/InputPeer.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->getAllDrafts](messages.getAllDrafts.md)() == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->getAllStickers](messages.getAllStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages\_AllStickers.md);  

$MadelineProto->[messages->getArchivedStickers](messages.getArchivedStickers.md)(\['masks' => [Bool](../types/Bool.md), 'offset\_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$messages\_ArchivedStickers](../types/messages\_ArchivedStickers.md);  

$MadelineProto->[messages->getAttachedStickers](messages.getAttachedStickers.md)(\['media' => [InputStickeredMedia](../types/InputStickeredMedia.md), \]) == [$Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md);  

$MadelineProto->[messages->getBotCallbackAnswer](messages.getBotCallbackAnswer.md)(\['game' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'msg\_id' => [int](../types/int.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_BotCallbackAnswer](../types/messages\_BotCallbackAnswer.md);  

$MadelineProto->[messages->getChats](messages.getChats.md)(\['id' => [[int](../types/int.md)], \]) == [$messages\_Chats](../types/messages\_Chats.md);  

$MadelineProto->[messages->getDhConfig](messages.getDhConfig.md)(\['version' => [int](../types/int.md), 'random\_length' => [int](../types/int.md), \]) == [$messages\_DhConfig](../types/messages\_DhConfig.md);  

$MadelineProto->[messages->getDialogs](messages.getDialogs.md)(\['offset\_date' => [int](../types/int.md), 'offset\_id' => [int](../types/int.md), 'offset\_peer' => [InputPeer](../types/InputPeer.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Dialogs](../types/messages\_Dialogs.md);  

$MadelineProto->[messages->getDocumentByHash](messages.getDocumentByHash.md)(\['sha256' => [bytes](../types/bytes.md), 'size' => [int](../types/int.md), 'mime\_type' => [string](../types/string.md), \]) == [$Document](../types/Document.md);  

$MadelineProto->[messages->getFeaturedStickers](messages.getFeaturedStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_FeaturedStickers](../types/messages\_FeaturedStickers.md);  

$MadelineProto->[messages->getFullChat](messages.getFullChat.md)(\['chat\_id' => [int](../types/int.md), \]) == [$messages\_ChatFull](../types/messages\_ChatFull.md);  

$MadelineProto->[messages->getGameHighScores](messages.getGameHighScores.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages\_HighScores.md);  

$MadelineProto->[messages->getHistory](messages.getHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'offset\_id' => [int](../types/int.md), 'offset\_date' => [int](../types/int.md), 'add\_offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), 'min\_id' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages\_Messages.md);  

$MadelineProto->[messages->getInlineBotResults](messages.getInlineBotResults.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \]) == [$messages\_BotResults](../types/messages\_BotResults.md);  

$MadelineProto->[messages->getInlineGameHighScores](messages.getInlineGameHighScores.md)(\['id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user\_id' => [InputUser](../types/InputUser.md), \]) == [$messages\_HighScores](../types/messages\_HighScores.md);  

$MadelineProto->[messages->getMaskStickers](messages.getMaskStickers.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_AllStickers](../types/messages\_AllStickers.md);  

$MadelineProto->[messages->getMessageEditData](messages.getMessageEditData.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), \]) == [$messages\_MessageEditData](../types/messages\_MessageEditData.md);  

$MadelineProto->[messages->getMessages](messages.getMessages.md)(\['id' => [[int](../types/int.md)], \]) == [$messages\_Messages](../types/messages\_Messages.md);  

$MadelineProto->[messages->getMessagesViews](messages.getMessagesViews.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'id' => [[int](../types/int.md)], 'increment' => [Bool](../types/Bool.md), \]) == [$Vector\_of\_int](../types/int.md);  

$MadelineProto->[messages->getPeerDialogs](messages.getPeerDialogs.md)(\['peers' => [[InputPeer](../types/InputPeer.md)], \]) == [$messages\_PeerDialogs](../types/messages\_PeerDialogs.md);  

$MadelineProto->[messages->getPeerSettings](messages.getPeerSettings.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$PeerSettings](../types/PeerSettings.md);  

$MadelineProto->[messages->getRecentStickers](messages.getRecentStickers.md)(\['attached' => [Bool](../types/Bool.md), 'hash' => [int](../types/int.md), \]) == [$messages\_RecentStickers](../types/messages\_RecentStickers.md);  

$MadelineProto->[messages->getSavedGifs](messages.getSavedGifs.md)(\['hash' => [int](../types/int.md), \]) == [$messages\_SavedGifs](../types/messages\_SavedGifs.md);  

$MadelineProto->[messages->getStickerSet](messages.getStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$messages\_StickerSet](../types/messages\_StickerSet.md);  

$MadelineProto->[messages->getWebPagePreview](messages.getWebPagePreview.md)(\['message' => [string](../types/string.md), \]) == [$MessageMedia](../types/MessageMedia.md);  

$MadelineProto->[messages->hideReportSpam](messages.hideReportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->importChatInvite](messages.importChatInvite.md)(\['hash' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->installStickerSet](messages.installStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'archived' => [Bool](../types/Bool.md), \]) == [$messages\_StickerSetInstallResult](../types/messages\_StickerSetInstallResult.md);  

$MadelineProto->[messages->migrateChat](messages.migrateChat.md)(\['chat\_id' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->readEncryptedHistory](messages.readEncryptedHistory.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'max\_date' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->readFeaturedStickers](messages.readFeaturedStickers.md)(\['id' => [[long](../types/long.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->readHistory](messages.readHistory.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'max\_id' => [int](../types/int.md), \]) == [$messages\_AffectedMessages](../types/messages\_AffectedMessages.md);  

$MadelineProto->[messages->readMessageContents](messages.readMessageContents.md)(\['id' => [[int](../types/int.md)], \]) == [$messages\_AffectedMessages](../types/messages\_AffectedMessages.md);  

$MadelineProto->[messages->receivedMessages](messages.receivedMessages.md)(\['max\_id' => [int](../types/int.md), \]) == [$Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md);  

$MadelineProto->[messages->receivedQueue](messages.receivedQueue.md)(\['max\_qts' => [int](../types/int.md), \]) == [$Vector\_of\_long](../types/long.md);  

$MadelineProto->[messages->reorderStickerSets](messages.reorderStickerSets.md)(\['masks' => [Bool](../types/Bool.md), 'order' => [[long](../types/long.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->reportSpam](messages.reportSpam.md)(\['peer' => [InputPeer](../types/InputPeer.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->requestEncryption](messages.requestEncryption.md)(\['user\_id' => [InputUser](../types/InputUser.md), 'random\_id' => [int](../types/int.md), 'g\_a' => [bytes](../types/bytes.md), \]) == [$EncryptedChat](../types/EncryptedChat.md);  

$MadelineProto->[messages->saveDraft](messages.saveDraft.md)(\['no\_webpage' => [Bool](../types/Bool.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'peer' => [InputPeer](../types/InputPeer.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->saveGif](messages.saveGif.md)(\['id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->saveRecentSticker](messages.saveRecentSticker.md)(\['attached' => [Bool](../types/Bool.md), 'id' => [InputDocument](../types/InputDocument.md), 'unsave' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->search](messages.search.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'q' => [string](../types/string.md), 'filter' => [MessagesFilter](../types/MessagesFilter.md), 'min\_date' => [int](../types/int.md), 'max\_date' => [int](../types/int.md), 'offset' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages\_Messages.md);  

$MadelineProto->[messages->searchGifs](messages.searchGifs.md)(\['q' => [string](../types/string.md), 'offset' => [int](../types/int.md), \]) == [$messages\_FoundGifs](../types/messages\_FoundGifs.md);  

$MadelineProto->[messages->searchGlobal](messages.searchGlobal.md)(\['q' => [string](../types/string.md), 'offset\_date' => [int](../types/int.md), 'offset\_peer' => [InputPeer](../types/InputPeer.md), 'offset\_id' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$messages\_Messages](../types/messages\_Messages.md);  

$MadelineProto->[messages->sendEncrypted](messages.sendEncrypted.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random\_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md);  

$MadelineProto->[messages->sendEncryptedFile](messages.sendEncryptedFile.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random\_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'file' => [InputEncryptedFile](../types/InputEncryptedFile.md), \]) == [$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md);  

$MadelineProto->[messages->sendEncryptedService](messages.sendEncryptedService.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'random\_id' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), \]) == [$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md);  

$MadelineProto->[messages->sendInlineBotResult](messages.sendInlineBotResult.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear\_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'random\_id' => [long](../types/long.md), 'query\_id' => [long](../types/long.md), 'id' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->sendMedia](messages.sendMedia.md)(\['silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear\_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'media' => [InputMedia](../types/InputMedia.md), 'random\_id' => [long](../types/long.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->sendMessage](messages.sendMessage.md)(\['no\_webpage' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'background' => [Bool](../types/Bool.md), 'clear\_draft' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'random\_id' => [long](../types/long.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->setBotCallbackAnswer](messages.setBotCallbackAnswer.md)(\['alert' => [Bool](../types/Bool.md), 'query\_id' => [long](../types/long.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->setEncryptedTyping](messages.setEncryptedTyping.md)(\['peer' => [InputEncryptedChat](../types/InputEncryptedChat.md), 'typing' => [Bool](../types/Bool.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->setGameScore](messages.setGameScore.md)(\['edit\_message' => [Bool](../types/Bool.md), 'peer' => [InputPeer](../types/InputPeer.md), 'id' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->setInlineBotResults](messages.setInlineBotResults.md)(\['gallery' => [Bool](../types/Bool.md), 'private' => [Bool](../types/Bool.md), 'query\_id' => [long](../types/long.md), 'results' => [[InputBotInlineResult](../types/InputBotInlineResult.md)], 'cache\_time' => [int](../types/int.md), 'next\_offset' => [string](../types/string.md), 'switch\_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->setInlineGameScore](messages.setInlineGameScore.md)(\['edit\_message' => [Bool](../types/Bool.md), 'id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'user\_id' => [InputUser](../types/InputUser.md), 'score' => [int](../types/int.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->setTyping](messages.setTyping.md)(\['peer' => [InputPeer](../types/InputPeer.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[messages->startBot](messages.startBot.md)(\['bot' => [InputUser](../types/InputUser.md), 'peer' => [InputPeer](../types/InputPeer.md), 'random\_id' => [long](../types/long.md), 'start\_param' => [string](../types/string.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->toggleChatAdmins](messages.toggleChatAdmins.md)(\['chat\_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), \]) == [$Updates](../types/Updates.md);  

$MadelineProto->[messages->uninstallStickerSet](messages.uninstallStickerSet.md)(\['stickerset' => [InputStickerSet](../types/InputStickerSet.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[photos->deletePhotos](photos.deletePhotos.md)(\['id' => [[InputPhoto](../types/InputPhoto.md)], \]) == [$Vector\_of\_long](../types/long.md);  

$MadelineProto->[photos->getUserPhotos](photos.getUserPhotos.md)(\['user\_id' => [InputUser](../types/InputUser.md), 'offset' => [int](../types/int.md), 'max\_id' => [long](../types/long.md), 'limit' => [int](../types/int.md), \]) == [$photos\_Photos](../types/photos\_Photos.md);  

$MadelineProto->[photos->updateProfilePhoto](photos.updateProfilePhoto.md)(\['id' => [InputPhoto](../types/InputPhoto.md), \]) == [$UserProfilePhoto](../types/UserProfilePhoto.md);  

$MadelineProto->[photos->uploadProfilePhoto](photos.uploadProfilePhoto.md)(\['file' => [InputFile](../types/InputFile.md), \]) == [$photos\_Photo](../types/photos\_Photo.md);  

$MadelineProto->[updates->getChannelDifference](updates.getChannelDifference.md)(\['channel' => [InputChannel](../types/InputChannel.md), 'filter' => [ChannelMessagesFilter](../types/ChannelMessagesFilter.md), 'pts' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$updates\_ChannelDifference](../types/updates\_ChannelDifference.md);  

$MadelineProto->[updates->getDifference](updates.getDifference.md)(\['pts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'qts' => [int](../types/int.md), \]) == [$updates\_Difference](../types/updates\_Difference.md);  

$MadelineProto->[updates->getState](updates.getState.md)() == [$updates\_State](../types/updates\_State.md);  

$MadelineProto->[upload->getFile](upload.getFile.md)(\['location' => [InputFileLocation](../types/InputFileLocation.md), 'offset' => [int](../types/int.md), 'limit' => [int](../types/int.md), \]) == [$upload\_File](../types/upload\_File.md);  

$MadelineProto->[upload->saveBigFilePart](upload.saveBigFilePart.md)(\['file\_id' => [long](../types/long.md), 'file\_part' => [int](../types/int.md), 'file\_total\_parts' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[upload->saveFilePart](upload.saveFilePart.md)(\['file\_id' => [long](../types/long.md), 'file\_part' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]) == [$Bool](../types/Bool.md);  

$MadelineProto->[users->getFullUser](users.getFullUser.md)(\['id' => [InputUser](../types/InputUser.md), \]) == [$UserFull](../types/UserFull.md);  

$MadelineProto->[users->getUsers](users.getUsers.md)(\['id' => [[InputUser](../types/InputUser.md)], \]) == [$Vector\_of\_User](../types/User.md);  

