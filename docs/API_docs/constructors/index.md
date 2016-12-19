# Constructors  

[$AccountDaysTTL](../types/AccountDaysTTL.md)\['[accountDaysTTL](accountDaysTTL.md)'\] = \['days' => [int](../types/int.md), \]  

[$account\_Authorizations](../types/account\_Authorizations.md)\['[account\_authorizations](account\_authorizations.md)'\] = \['authorizations' => [[Authorization](../types/Authorization.md)], \]  

[$account\_Password](../types/account\_Password.md)\['[account\_noPassword](account\_noPassword.md)'\] = \['new\_salt' => [bytes](../types/bytes.md), 'email\_unconfirmed\_pattern' => [string](../types/string.md), \]  

[$account\_Password](../types/account\_Password.md)\['[account\_password](account\_password.md)'\] = \['current\_salt' => [bytes](../types/bytes.md), 'new\_salt' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'has\_recovery' => [Bool](../types/Bool.md), 'email\_unconfirmed\_pattern' => [string](../types/string.md), \]  

[$account\_PasswordInputSettings](../types/account\_PasswordInputSettings.md)\['[account\_passwordInputSettings](account\_passwordInputSettings.md)'\] = \['new\_salt' => [bytes](../types/bytes.md), 'new\_password\_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'email' => [string](../types/string.md), \]  

[$account\_PasswordSettings](../types/account\_PasswordSettings.md)\['[account\_passwordSettings](account\_passwordSettings.md)'\] = \['email' => [string](../types/string.md), \]  

[$account\_PrivacyRules](../types/account\_PrivacyRules.md)\['[account\_privacyRules](account\_privacyRules.md)'\] = \['rules' => [[PrivacyRule](../types/PrivacyRule.md)], 'users' => [[User](../types/User.md)], \]  

[$auth\_Authorization](../types/auth\_Authorization.md)\['[auth\_authorization](auth\_authorization.md)'\] = \['tmp\_sessions' => [int](../types/int.md), 'user' => [User](../types/User.md), \]  

[$auth\_CheckedPhone](../types/auth\_CheckedPhone.md)\['[auth\_checkedPhone](auth\_checkedPhone.md)'\] = \['phone\_registered' => [Bool](../types/Bool.md), \]  

[$auth\_CodeType](../types/auth\_CodeType.md)\['[auth\_codeTypeCall](auth\_codeTypeCall.md)'\] =   

[$auth\_CodeType](../types/auth\_CodeType.md)\['[auth\_codeTypeFlashCall](auth\_codeTypeFlashCall.md)'\] =   

[$auth\_CodeType](../types/auth\_CodeType.md)\['[auth\_codeTypeSms](auth\_codeTypeSms.md)'\] =   

[$auth\_ExportedAuthorization](../types/auth\_ExportedAuthorization.md)\['[auth\_exportedAuthorization](auth\_exportedAuthorization.md)'\] = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]  

[$auth\_PasswordRecovery](../types/auth\_PasswordRecovery.md)\['[auth\_passwordRecovery](auth\_passwordRecovery.md)'\] = \['email\_pattern' => [string](../types/string.md), \]  

[$auth\_SentCode](../types/auth\_SentCode.md)\['[auth\_sentCode](auth\_sentCode.md)'\] = \['phone\_registered' => [Bool](../types/Bool.md), 'type' => [auth\_SentCodeType](../types/auth\_SentCodeType.md), 'phone\_code\_hash' => [string](../types/string.md), 'next\_type' => [auth\_CodeType](../types/auth\_CodeType.md), 'timeout' => [int](../types/int.md), \]  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md)\['[auth\_sentCodeTypeApp](auth\_sentCodeTypeApp.md)'\] = \['length' => [int](../types/int.md), \]  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md)\['[auth\_sentCodeTypeCall](auth\_sentCodeTypeCall.md)'\] = \['length' => [int](../types/int.md), \]  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md)\['[auth\_sentCodeTypeFlashCall](auth\_sentCodeTypeFlashCall.md)'\] = \['pattern' => [string](../types/string.md), \]  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md)\['[auth\_sentCodeTypeSms](auth\_sentCodeTypeSms.md)'\] = \['length' => [int](../types/int.md), \]  

[$Authorization](../types/Authorization.md)\['[authorization](authorization.md)'\] = \['hash' => [long](../types/long.md), 'device\_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system\_version' => [string](../types/string.md), 'api\_id' => [int](../types/int.md), 'app\_name' => [string](../types/string.md), 'app\_version' => [string](../types/string.md), 'date\_created' => [int](../types/int.md), 'date\_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \]  

[$Bool](../types/Bool.md)\['[boolFalse](boolFalse.md)'\] =   

[$Bool](../types/Bool.md)\['[boolTrue](boolTrue.md)'\] =   

[$BotCommand](../types/BotCommand.md)\['[botCommand](botCommand.md)'\] = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \]  

[$BotInfo](../types/BotInfo.md)\['[botInfo](botInfo.md)'\] = \['user\_id' => [int](../types/int.md), 'description' => [string](../types/string.md), 'commands' => [[BotCommand](../types/BotCommand.md)], \]  

[$BotInlineResult](../types/BotInlineResult.md)\['[botInlineMediaResult](botInlineMediaResult.md)'\] = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'send\_message' => [BotInlineMessage](../types/BotInlineMessage.md), \]  

[$BotInlineMessage](../types/BotInlineMessage.md)\['[botInlineMessageMediaAuto](botInlineMessageMediaAuto.md)'\] = \['caption' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$BotInlineMessage](../types/BotInlineMessage.md)\['[botInlineMessageMediaContact](botInlineMessageMediaContact.md)'\] = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$BotInlineMessage](../types/BotInlineMessage.md)\['[botInlineMessageMediaGeo](botInlineMessageMediaGeo.md)'\] = \['geo' => [GeoPoint](../types/GeoPoint.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$BotInlineMessage](../types/BotInlineMessage.md)\['[botInlineMessageMediaVenue](botInlineMessageMediaVenue.md)'\] = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$BotInlineMessage](../types/BotInlineMessage.md)\['[botInlineMessageText](botInlineMessageText.md)'\] = \['no\_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$BotInlineResult](../types/BotInlineResult.md)\['[botInlineResult](botInlineResult.md)'\] = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send\_message' => [BotInlineMessage](../types/BotInlineMessage.md), \]  

[$Chat](../types/Chat.md)\['[channel](channel.md)'\] = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'editor' => [Bool](../types/Bool.md), 'moderator' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'democracy' => [Bool](../types/Bool.md), 'signatures' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'restriction\_reason' => [string](../types/string.md), \]  

[$Chat](../types/Chat.md)\['[channelForbidden](channelForbidden.md)'\] = \['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), \]  

[$ChatFull](../types/ChatFull.md)\['[channelFull](channelFull.md)'\] = \['can\_view\_participants' => [Bool](../types/Bool.md), 'can\_set\_username' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants\_count' => [int](../types/int.md), 'admins\_count' => [int](../types/int.md), 'kicked\_count' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'chat\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported\_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot\_info' => [[BotInfo](../types/BotInfo.md)], 'migrated\_from\_chat\_id' => [int](../types/int.md), 'migrated\_from\_max\_id' => [int](../types/int.md), 'pinned\_msg\_id' => [int](../types/int.md), \]  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md)\['[channelMessagesFilter](channelMessagesFilter.md)'\] = \['exclude\_new\_messages' => [Bool](../types/Bool.md), 'ranges' => [[MessageRange](../types/MessageRange.md)], \]  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md)\['[channelMessagesFilterEmpty](channelMessagesFilterEmpty.md)'\] =   

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipant](channelParticipant.md)'\] = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipantCreator](channelParticipantCreator.md)'\] = \['user\_id' => [int](../types/int.md), \]  

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipantEditor](channelParticipantEditor.md)'\] = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipantKicked](channelParticipantKicked.md)'\] = \['user\_id' => [int](../types/int.md), 'kicked\_by' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipantModerator](channelParticipantModerator.md)'\] = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChannelParticipant](../types/ChannelParticipant.md)\['[channelParticipantSelf](channelParticipantSelf.md)'\] = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md)\['[channelParticipantsAdmins](channelParticipantsAdmins.md)'\] =   

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md)\['[channelParticipantsBots](channelParticipantsBots.md)'\] =   

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md)\['[channelParticipantsKicked](channelParticipantsKicked.md)'\] =   

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md)\['[channelParticipantsRecent](channelParticipantsRecent.md)'\] =   

[$ChannelParticipantRole](../types/ChannelParticipantRole.md)\['[channelRoleEditor](channelRoleEditor.md)'\] =   

[$ChannelParticipantRole](../types/ChannelParticipantRole.md)\['[channelRoleEmpty](channelRoleEmpty.md)'\] =   

[$ChannelParticipantRole](../types/ChannelParticipantRole.md)\['[channelRoleModerator](channelRoleModerator.md)'\] =   

[$channels\_ChannelParticipant](../types/channels\_ChannelParticipant.md)\['[channels\_channelParticipant](channels\_channelParticipant.md)'\] = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => [[User](../types/User.md)], \]  

[$channels\_ChannelParticipants](../types/channels\_ChannelParticipants.md)\['[channels\_channelParticipants](channels\_channelParticipants.md)'\] = \['count' => [int](../types/int.md), 'participants' => [[ChannelParticipant](../types/ChannelParticipant.md)], 'users' => [[User](../types/User.md)], \]  

[$Chat](../types/Chat.md)\['[chat](chat.md)'\] = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'admins\_enabled' => [Bool](../types/Bool.md), 'admin' => [Bool](../types/Bool.md), 'deactivated' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'migrated\_to' => [InputChannel](../types/InputChannel.md), \]  

[$Chat](../types/Chat.md)\['[chatEmpty](chatEmpty.md)'\] = \['id' => [int](../types/int.md), \]  

[$Chat](../types/Chat.md)\['[chatForbidden](chatForbidden.md)'\] = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), \]  

[$ChatFull](../types/ChatFull.md)\['[chatFull](chatFull.md)'\] = \['id' => [int](../types/int.md), 'participants' => [ChatParticipants](../types/ChatParticipants.md), 'chat\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported\_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot\_info' => [[BotInfo](../types/BotInfo.md)], \]  

[$ChatInvite](../types/ChatInvite.md)\['[chatInvite](chatInvite.md)'\] = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants\_count' => [int](../types/int.md), 'participants' => [[User](../types/User.md)], \]  

[$ChatInvite](../types/ChatInvite.md)\['[chatInviteAlready](chatInviteAlready.md)'\] = \['chat' => [Chat](../types/Chat.md), \]  

[$ExportedChatInvite](../types/ExportedChatInvite.md)\['[chatInviteEmpty](chatInviteEmpty.md)'\] =   

[$ExportedChatInvite](../types/ExportedChatInvite.md)\['[chatInviteExported](chatInviteExported.md)'\] = \['link' => [string](../types/string.md), \]  

[$ChatParticipant](../types/ChatParticipant.md)\['[chatParticipant](chatParticipant.md)'\] = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChatParticipant](../types/ChatParticipant.md)\['[chatParticipantAdmin](chatParticipantAdmin.md)'\] = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ChatParticipant](../types/ChatParticipant.md)\['[chatParticipantCreator](chatParticipantCreator.md)'\] = \['user\_id' => [int](../types/int.md), \]  

[$ChatParticipants](../types/ChatParticipants.md)\['[chatParticipants](chatParticipants.md)'\] = \['chat\_id' => [int](../types/int.md), 'participants' => [[ChatParticipant](../types/ChatParticipant.md)], 'version' => [int](../types/int.md), \]  

[$ChatParticipants](../types/ChatParticipants.md)\['[chatParticipantsForbidden](chatParticipantsForbidden.md)'\] = \['chat\_id' => [int](../types/int.md), 'self\_participant' => [ChatParticipant](../types/ChatParticipant.md), \]  

[$ChatPhoto](../types/ChatPhoto.md)\['[chatPhoto](chatPhoto.md)'\] = \['photo\_small' => [FileLocation](../types/FileLocation.md), 'photo\_big' => [FileLocation](../types/FileLocation.md), \]  

[$ChatPhoto](../types/ChatPhoto.md)\['[chatPhotoEmpty](chatPhotoEmpty.md)'\] =   

[$Config](../types/Config.md)\['[config](config.md)'\] = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test\_mode' => [Bool](../types/Bool.md), 'this\_dc' => [int](../types/int.md), 'dc\_options' => [[DcOption](../types/DcOption.md)], 'chat\_size\_max' => [int](../types/int.md), 'megagroup\_size\_max' => [int](../types/int.md), 'forwarded\_count\_max' => [int](../types/int.md), 'online\_update\_period\_ms' => [int](../types/int.md), 'offline\_blur\_timeout\_ms' => [int](../types/int.md), 'offline\_idle\_timeout\_ms' => [int](../types/int.md), 'online\_cloud\_timeout\_ms' => [int](../types/int.md), 'notify\_cloud\_delay\_ms' => [int](../types/int.md), 'notify\_default\_delay\_ms' => [int](../types/int.md), 'chat\_big\_size' => [int](../types/int.md), 'push\_chat\_period\_ms' => [int](../types/int.md), 'push\_chat\_limit' => [int](../types/int.md), 'saved\_gifs\_limit' => [int](../types/int.md), 'edit\_time\_limit' => [int](../types/int.md), 'rating\_e\_decay' => [int](../types/int.md), 'stickers\_recent\_limit' => [int](../types/int.md), 'tmp\_sessions' => [int](../types/int.md), 'disabled\_features' => [[DisabledFeature](../types/DisabledFeature.md)], \]  

[$Contact](../types/Contact.md)\['[contact](contact.md)'\] = \['user\_id' => [int](../types/int.md), 'mutual' => [Bool](../types/Bool.md), \]  

[$ContactBlocked](../types/ContactBlocked.md)\['[contactBlocked](contactBlocked.md)'\] = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$ContactLink](../types/ContactLink.md)\['[contactLinkContact](contactLinkContact.md)'\] =   

[$ContactLink](../types/ContactLink.md)\['[contactLinkHasPhone](contactLinkHasPhone.md)'\] =   

[$ContactLink](../types/ContactLink.md)\['[contactLinkNone](contactLinkNone.md)'\] =   

[$ContactLink](../types/ContactLink.md)\['[contactLinkUnknown](contactLinkUnknown.md)'\] =   

[$ContactStatus](../types/ContactStatus.md)\['[contactStatus](contactStatus.md)'\] = \['user\_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \]  

[$contacts\_Blocked](../types/contacts\_Blocked.md)\['[contacts\_blocked](contacts\_blocked.md)'\] = \['blocked' => [[ContactBlocked](../types/ContactBlocked.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_Blocked](../types/contacts\_Blocked.md)\['[contacts\_blockedSlice](contacts\_blockedSlice.md)'\] = \['count' => [int](../types/int.md), 'blocked' => [[ContactBlocked](../types/ContactBlocked.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_Contacts](../types/contacts\_Contacts.md)\['[contacts\_contacts](contacts\_contacts.md)'\] = \['contacts' => [[Contact](../types/Contact.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_Contacts](../types/contacts\_Contacts.md)\['[contacts\_contactsNotModified](contacts\_contactsNotModified.md)'\] =   

[$contacts\_Found](../types/contacts\_Found.md)\['[contacts\_found](contacts\_found.md)'\] = \['results' => [[Peer](../types/Peer.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_ImportedContacts](../types/contacts\_ImportedContacts.md)\['[contacts\_importedContacts](contacts\_importedContacts.md)'\] = \['imported' => [[ImportedContact](../types/ImportedContact.md)], 'retry\_contacts' => [[long](../types/long.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_Link](../types/contacts\_Link.md)\['[contacts\_link](contacts\_link.md)'\] = \['my\_link' => [ContactLink](../types/ContactLink.md), 'foreign\_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \]  

[$contacts\_ResolvedPeer](../types/contacts\_ResolvedPeer.md)\['[contacts\_resolvedPeer](contacts\_resolvedPeer.md)'\] = \['peer' => [Peer](../types/Peer.md), 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_TopPeers](../types/contacts\_TopPeers.md)\['[contacts\_topPeers](contacts\_topPeers.md)'\] = \['categories' => [[TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$contacts\_TopPeers](../types/contacts\_TopPeers.md)\['[contacts\_topPeersNotModified](contacts\_topPeersNotModified.md)'\] =   

[$DcOption](../types/DcOption.md)\['[dcOption](dcOption.md)'\] = \['ipv6' => [Bool](../types/Bool.md), 'media\_only' => [Bool](../types/Bool.md), 'tcpo\_only' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip\_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \]  

[$Dialog](../types/Dialog.md)\['[dialog](dialog.md)'\] = \['peer' => [Peer](../types/Peer.md), 'top\_message' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \]  

[$DisabledFeature](../types/DisabledFeature.md)\['[disabledFeature](disabledFeature.md)'\] = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \]  

[$Document](../types/Document.md)\['[document](document.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime\_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc\_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], \]  

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeAnimated](documentAttributeAnimated.md)'\] =   

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeAudio](documentAttributeAudio.md)'\] = \['voice' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'waveform' => [bytes](../types/bytes.md), \]  

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeFilename](documentAttributeFilename.md)'\] = \['file\_name' => [string](../types/string.md), \]  

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeHasStickers](documentAttributeHasStickers.md)'\] =   

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeImageSize](documentAttributeImageSize.md)'\] = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \]  

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeSticker](documentAttributeSticker.md)'\] = \['mask' => [Bool](../types/Bool.md), 'alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'mask\_coords' => [MaskCoords](../types/MaskCoords.md), \]  

[$DocumentAttribute](../types/DocumentAttribute.md)\['[documentAttributeVideo](documentAttributeVideo.md)'\] = \['duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \]  

[$Document](../types/Document.md)\['[documentEmpty](documentEmpty.md)'\] = \['id' => [long](../types/long.md), \]  

[$DraftMessage](../types/DraftMessage.md)\['[draftMessage](draftMessage.md)'\] = \['no\_webpage' => [Bool](../types/Bool.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'date' => [int](../types/int.md), \]  

[$DraftMessage](../types/DraftMessage.md)\['[draftMessageEmpty](draftMessageEmpty.md)'\] =   

[$EncryptedChat](../types/EncryptedChat.md)\['[encryptedChat](encryptedChat.md)'\] = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), 'g\_a\_or\_b' => [bytes](../types/bytes.md), 'key\_fingerprint' => [long](../types/long.md), \]  

[$EncryptedChat](../types/EncryptedChat.md)\['[encryptedChatDiscarded](encryptedChatDiscarded.md)'\] = \['id' => [int](../types/int.md), \]  

[$EncryptedChat](../types/EncryptedChat.md)\['[encryptedChatEmpty](encryptedChatEmpty.md)'\] = \['id' => [int](../types/int.md), \]  

[$EncryptedChat](../types/EncryptedChat.md)\['[encryptedChatRequested](encryptedChatRequested.md)'\] = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), 'g\_a' => [bytes](../types/bytes.md), \]  

[$EncryptedChat](../types/EncryptedChat.md)\['[encryptedChatWaiting](encryptedChatWaiting.md)'\] = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), \]  

[$EncryptedFile](../types/EncryptedFile.md)\['[encryptedFile](encryptedFile.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'dc\_id' => [int](../types/int.md), 'key\_fingerprint' => [int](../types/int.md), \]  

[$EncryptedFile](../types/EncryptedFile.md)\['[encryptedFileEmpty](encryptedFileEmpty.md)'\] =   

[$EncryptedMessage](../types/EncryptedMessage.md)\['[encryptedMessage](encryptedMessage.md)'\] = \['random\_id' => [long](../types/long.md), 'chat\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \]  

[$EncryptedMessage](../types/EncryptedMessage.md)\['[encryptedMessageService](encryptedMessageService.md)'\] = \['random\_id' => [long](../types/long.md), 'chat\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]  

[$Error](../types/Error.md)\['[error](error.md)'\] = \['code' => [int](../types/int.md), 'text' => [string](../types/string.md), \]  

[$ExportedMessageLink](../types/ExportedMessageLink.md)\['[exportedMessageLink](exportedMessageLink.md)'\] = \['link' => [string](../types/string.md), \]  

[$FileLocation](../types/FileLocation.md)\['[fileLocation](fileLocation.md)'\] = \['dc\_id' => [int](../types/int.md), 'volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \]  

[$FileLocation](../types/FileLocation.md)\['[fileLocationUnavailable](fileLocationUnavailable.md)'\] = \['volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \]  

[$FoundGif](../types/FoundGif.md)\['[foundGif](foundGif.md)'\] = \['url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \]  

[$FoundGif](../types/FoundGif.md)\['[foundGifCached](foundGifCached.md)'\] = \['url' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \]  

[$Game](../types/Game.md)\['[game](game.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'short\_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \]  

[$GeoPoint](../types/GeoPoint.md)\['[geoPoint](geoPoint.md)'\] = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \]  

[$GeoPoint](../types/GeoPoint.md)\['[geoPointEmpty](geoPointEmpty.md)'\] =   

[$help\_AppChangelog](../types/help\_AppChangelog.md)\['[help\_appChangelog](help\_appChangelog.md)'\] = \['text' => [string](../types/string.md), \]  

[$help\_AppChangelog](../types/help\_AppChangelog.md)\['[help\_appChangelogEmpty](help\_appChangelogEmpty.md)'\] =   

[$help\_AppUpdate](../types/help\_AppUpdate.md)\['[help\_appUpdate](help\_appUpdate.md)'\] = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \]  

[$help\_InviteText](../types/help\_InviteText.md)\['[help\_inviteText](help\_inviteText.md)'\] = \['message' => [string](../types/string.md), \]  

[$help\_AppUpdate](../types/help\_AppUpdate.md)\['[help\_noAppUpdate](help\_noAppUpdate.md)'\] =   

[$help\_Support](../types/help\_Support.md)\['[help\_support](help\_support.md)'\] = \['phone\_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \]  

[$help\_TermsOfService](../types/help\_TermsOfService.md)\['[help\_termsOfService](help\_termsOfService.md)'\] = \['text' => [string](../types/string.md), \]  

[$HighScore](../types/HighScore.md)\['[highScore](highScore.md)'\] = \['pos' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \]  

[$ImportedContact](../types/ImportedContact.md)\['[importedContact](importedContact.md)'\] = \['user\_id' => [int](../types/int.md), 'client\_id' => [long](../types/long.md), \]  

[$InlineBotSwitchPM](../types/InlineBotSwitchPM.md)\['[inlineBotSwitchPM](inlineBotSwitchPM.md)'\] = \['text' => [string](../types/string.md), 'start\_param' => [string](../types/string.md), \]  

[$InputAppEvent](../types/InputAppEvent.md)\['[inputAppEvent](inputAppEvent.md)'\] = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageGame](inputBotInlineMessageGame.md)'\] = \['reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineMessageID](../types/InputBotInlineMessageID.md)\['[inputBotInlineMessageID](inputBotInlineMessageID.md)'\] = \['dc\_id' => [int](../types/int.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageMediaAuto](inputBotInlineMessageMediaAuto.md)'\] = \['caption' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageMediaContact](inputBotInlineMessageMediaContact.md)'\] = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageMediaGeo](inputBotInlineMessageMediaGeo.md)'\] = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageMediaVenue](inputBotInlineMessageMediaVenue.md)'\] = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md)\['[inputBotInlineMessageText](inputBotInlineMessageText.md)'\] = \['no\_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \]  

[$InputBotInlineResult](../types/InputBotInlineResult.md)\['[inputBotInlineResult](inputBotInlineResult.md)'\] = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \]  

[$InputBotInlineResult](../types/InputBotInlineResult.md)\['[inputBotInlineResultDocument](inputBotInlineResultDocument.md)'\] = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document' => [InputDocument](../types/InputDocument.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \]  

[$InputBotInlineResult](../types/InputBotInlineResult.md)\['[inputBotInlineResultGame](inputBotInlineResultGame.md)'\] = \['id' => [string](../types/string.md), 'short\_name' => [string](../types/string.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \]  

[$InputBotInlineResult](../types/InputBotInlineResult.md)\['[inputBotInlineResultPhoto](inputBotInlineResultPhoto.md)'\] = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [InputPhoto](../types/InputPhoto.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \]  

[$InputChannel](../types/InputChannel.md)\['[inputChannel](inputChannel.md)'\] = \['channel\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputChannel](../types/InputChannel.md)\['[inputChannelEmpty](inputChannelEmpty.md)'\] =   

[$InputChatPhoto](../types/InputChatPhoto.md)\['[inputChatPhoto](inputChatPhoto.md)'\] = \['id' => [InputPhoto](../types/InputPhoto.md), \]  

[$InputChatPhoto](../types/InputChatPhoto.md)\['[inputChatPhotoEmpty](inputChatPhotoEmpty.md)'\] =   

[$InputChatPhoto](../types/InputChatPhoto.md)\['[inputChatUploadedPhoto](inputChatUploadedPhoto.md)'\] = \['file' => [InputFile](../types/InputFile.md), \]  

[$InputDocument](../types/InputDocument.md)\['[inputDocument](inputDocument.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputDocument](../types/InputDocument.md)\['[inputDocumentEmpty](inputDocumentEmpty.md)'\] =   

[$InputFileLocation](../types/InputFileLocation.md)\['[inputDocumentFileLocation](inputDocumentFileLocation.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'version' => [int](../types/int.md), \]  

[$InputEncryptedChat](../types/InputEncryptedChat.md)\['[inputEncryptedChat](inputEncryptedChat.md)'\] = \['chat\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputEncryptedFile](../types/InputEncryptedFile.md)\['[inputEncryptedFile](inputEncryptedFile.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputEncryptedFile](../types/InputEncryptedFile.md)\['[inputEncryptedFileBigUploaded](inputEncryptedFileBigUploaded.md)'\] = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'key\_fingerprint' => [int](../types/int.md), \]  

[$InputEncryptedFile](../types/InputEncryptedFile.md)\['[inputEncryptedFileEmpty](inputEncryptedFileEmpty.md)'\] =   

[$InputFileLocation](../types/InputFileLocation.md)\['[inputEncryptedFileLocation](inputEncryptedFileLocation.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputEncryptedFile](../types/InputEncryptedFile.md)\['[inputEncryptedFileUploaded](inputEncryptedFileUploaded.md)'\] = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'md5\_checksum' => [string](../types/string.md), 'key\_fingerprint' => [int](../types/int.md), \]  

[$InputFile](../types/InputFile.md)\['[inputFile](inputFile.md)'\] = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), 'md5\_checksum' => [string](../types/string.md), \]  

[$InputFile](../types/InputFile.md)\['[inputFileBig](inputFileBig.md)'\] = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), \]  

[$InputFileLocation](../types/InputFileLocation.md)\['[inputFileLocation](inputFileLocation.md)'\] = \['volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \]  

[$InputGame](../types/InputGame.md)\['[inputGameID](inputGameID.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputGame](../types/InputGame.md)\['[inputGameShortName](inputGameShortName.md)'\] = \['bot\_id' => [InputUser](../types/InputUser.md), 'short\_name' => [string](../types/string.md), \]  

[$InputGeoPoint](../types/InputGeoPoint.md)\['[inputGeoPoint](inputGeoPoint.md)'\] = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \]  

[$InputGeoPoint](../types/InputGeoPoint.md)\['[inputGeoPointEmpty](inputGeoPointEmpty.md)'\] =   

[$InputMedia](../types/InputMedia.md)\['[inputMediaContact](inputMediaContact.md)'\] = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaDocument](inputMediaDocument.md)'\] = \['id' => [InputDocument](../types/InputDocument.md), 'caption' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaDocumentExternal](inputMediaDocumentExternal.md)'\] = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaEmpty](inputMediaEmpty.md)'\] =   

[$InputMedia](../types/InputMedia.md)\['[inputMediaGame](inputMediaGame.md)'\] = \['id' => [InputGame](../types/InputGame.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaGeoPoint](inputMediaGeoPoint.md)'\] = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaGifExternal](inputMediaGifExternal.md)'\] = \['url' => [string](../types/string.md), 'q' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaPhoto](inputMediaPhoto.md)'\] = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaPhotoExternal](inputMediaPhotoExternal.md)'\] = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaUploadedDocument](inputMediaUploadedDocument.md)'\] = \['file' => [InputFile](../types/InputFile.md), 'mime\_type' => [string](../types/string.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaUploadedPhoto](inputMediaUploadedPhoto.md)'\] = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaUploadedThumbDocument](inputMediaUploadedThumbDocument.md)'\] = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime\_type' => [string](../types/string.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \]  

[$InputMedia](../types/InputMedia.md)\['[inputMediaVenue](inputMediaVenue.md)'\] = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[inputMessageEntityMentionName](inputMessageEntityMentionName.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), \]  

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterChatPhotos](inputMessagesFilterChatPhotos.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterDocument](inputMessagesFilterDocument.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterEmpty](inputMessagesFilterEmpty.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterGif](inputMessagesFilterGif.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterMusic](inputMessagesFilterMusic.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterPhotoVideo](inputMessagesFilterPhotoVideo.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterPhotoVideoDocuments](inputMessagesFilterPhotoVideoDocuments.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterPhotos](inputMessagesFilterPhotos.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterUrl](inputMessagesFilterUrl.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterVideo](inputMessagesFilterVideo.md)'\] =   

[$MessagesFilter](../types/MessagesFilter.md)\['[inputMessagesFilterVoice](inputMessagesFilterVoice.md)'\] =   

[$InputNotifyPeer](../types/InputNotifyPeer.md)\['[inputNotifyAll](inputNotifyAll.md)'\] =   

[$InputNotifyPeer](../types/InputNotifyPeer.md)\['[inputNotifyChats](inputNotifyChats.md)'\] =   

[$InputNotifyPeer](../types/InputNotifyPeer.md)\['[inputNotifyPeer](inputNotifyPeer.md)'\] = \['peer' => [InputPeer](../types/InputPeer.md), \]  

[$InputNotifyPeer](../types/InputNotifyPeer.md)\['[inputNotifyUsers](inputNotifyUsers.md)'\] =   

[$InputPeer](../types/InputPeer.md)\['[inputPeerChannel](inputPeerChannel.md)'\] = \['channel\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputPeer](../types/InputPeer.md)\['[inputPeerChat](inputPeerChat.md)'\] = \['chat\_id' => [int](../types/int.md), \]  

[$InputPeer](../types/InputPeer.md)\['[inputPeerEmpty](inputPeerEmpty.md)'\] =   

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md)\['[inputPeerNotifyEventsAll](inputPeerNotifyEventsAll.md)'\] =   

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md)\['[inputPeerNotifyEventsEmpty](inputPeerNotifyEventsEmpty.md)'\] =   

[$InputPeerNotifySettings](../types/InputPeerNotifySettings.md)\['[inputPeerNotifySettings](inputPeerNotifySettings.md)'\] = \['show\_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute\_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \]  

[$InputPeer](../types/InputPeer.md)\['[inputPeerSelf](inputPeerSelf.md)'\] =   

[$InputPeer](../types/InputPeer.md)\['[inputPeerUser](inputPeerUser.md)'\] = \['user\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputContact](../types/InputContact.md)\['[inputPhoneContact](inputPhoneContact.md)'\] = \['client\_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), \]  

[$InputPhoto](../types/InputPhoto.md)\['[inputPhoto](inputPhoto.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputPhoto](../types/InputPhoto.md)\['[inputPhotoEmpty](inputPhotoEmpty.md)'\] =   

[$InputPrivacyKey](../types/InputPrivacyKey.md)\['[inputPrivacyKeyChatInvite](inputPrivacyKeyChatInvite.md)'\] =   

[$InputPrivacyKey](../types/InputPrivacyKey.md)\['[inputPrivacyKeyStatusTimestamp](inputPrivacyKeyStatusTimestamp.md)'\] =   

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueAllowAll](inputPrivacyValueAllowAll.md)'\] =   

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueAllowContacts](inputPrivacyValueAllowContacts.md)'\] =   

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueAllowUsers](inputPrivacyValueAllowUsers.md)'\] = \['users' => [[InputUser](../types/InputUser.md)], \]  

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueDisallowAll](inputPrivacyValueDisallowAll.md)'\] =   

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueDisallowContacts](inputPrivacyValueDisallowContacts.md)'\] =   

[$InputPrivacyRule](../types/InputPrivacyRule.md)\['[inputPrivacyValueDisallowUsers](inputPrivacyValueDisallowUsers.md)'\] = \['users' => [[InputUser](../types/InputUser.md)], \]  

[$ReportReason](../types/ReportReason.md)\['[inputReportReasonOther](inputReportReasonOther.md)'\] = \['text' => [string](../types/string.md), \]  

[$ReportReason](../types/ReportReason.md)\['[inputReportReasonPornography](inputReportReasonPornography.md)'\] =   

[$ReportReason](../types/ReportReason.md)\['[inputReportReasonSpam](inputReportReasonSpam.md)'\] =   

[$ReportReason](../types/ReportReason.md)\['[inputReportReasonViolence](inputReportReasonViolence.md)'\] =   

[$InputStickerSet](../types/InputStickerSet.md)\['[inputStickerSetEmpty](inputStickerSetEmpty.md)'\] =   

[$InputStickerSet](../types/InputStickerSet.md)\['[inputStickerSetID](inputStickerSetID.md)'\] = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputStickerSet](../types/InputStickerSet.md)\['[inputStickerSetShortName](inputStickerSetShortName.md)'\] = \['short\_name' => [string](../types/string.md), \]  

[$InputStickeredMedia](../types/InputStickeredMedia.md)\['[inputStickeredMediaDocument](inputStickeredMediaDocument.md)'\] = \['id' => [InputDocument](../types/InputDocument.md), \]  

[$InputStickeredMedia](../types/InputStickeredMedia.md)\['[inputStickeredMediaPhoto](inputStickeredMediaPhoto.md)'\] = \['id' => [InputPhoto](../types/InputPhoto.md), \]  

[$InputUser](../types/InputUser.md)\['[inputUser](inputUser.md)'\] = \['user\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \]  

[$InputUser](../types/InputUser.md)\['[inputUserEmpty](inputUserEmpty.md)'\] =   

[$InputUser](../types/InputUser.md)\['[inputUserSelf](inputUserSelf.md)'\] =   

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButton](keyboardButton.md)'\] = \['text' => [string](../types/string.md), \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonCallback](keyboardButtonCallback.md)'\] = \['text' => [string](../types/string.md), 'data' => [bytes](../types/bytes.md), \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonGame](keyboardButtonGame.md)'\] = \['text' => [string](../types/string.md), \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonRequestGeoLocation](keyboardButtonRequestGeoLocation.md)'\] = \['text' => [string](../types/string.md), \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonRequestPhone](keyboardButtonRequestPhone.md)'\] = \['text' => [string](../types/string.md), \]  

[$KeyboardButtonRow](../types/KeyboardButtonRow.md)\['[keyboardButtonRow](keyboardButtonRow.md)'\] = \['buttons' => [[KeyboardButton](../types/KeyboardButton.md)], \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonSwitchInline](keyboardButtonSwitchInline.md)'\] = \['same\_peer' => [Bool](../types/Bool.md), 'text' => [string](../types/string.md), 'query' => [string](../types/string.md), \]  

[$KeyboardButton](../types/KeyboardButton.md)\['[keyboardButtonUrl](keyboardButtonUrl.md)'\] = \['text' => [string](../types/string.md), 'url' => [string](../types/string.md), \]  

[$MaskCoords](../types/MaskCoords.md)\['[maskCoords](maskCoords.md)'\] = \['n' => [int](../types/int.md), 'x' => [double](../types/double.md), 'y' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \]  

[$Message](../types/Message.md)\['[message](message.md)'\] = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'to\_id' => [Peer](../types/Peer.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'views' => [int](../types/int.md), 'edit\_date' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChannelCreate](messageActionChannelCreate.md)'\] = \['title' => [string](../types/string.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChannelMigrateFrom](messageActionChannelMigrateFrom.md)'\] = \['title' => [string](../types/string.md), 'chat\_id' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatAddUser](messageActionChatAddUser.md)'\] = \['users' => [[int](../types/int.md)], \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatCreate](messageActionChatCreate.md)'\] = \['title' => [string](../types/string.md), 'users' => [[int](../types/int.md)], \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatDeletePhoto](messageActionChatDeletePhoto.md)'\] =   

[$MessageAction](../types/MessageAction.md)\['[messageActionChatDeleteUser](messageActionChatDeleteUser.md)'\] = \['user\_id' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatEditPhoto](messageActionChatEditPhoto.md)'\] = \['photo' => [Photo](../types/Photo.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatEditTitle](messageActionChatEditTitle.md)'\] = \['title' => [string](../types/string.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatJoinedByLink](messageActionChatJoinedByLink.md)'\] = \['inviter\_id' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionChatMigrateTo](messageActionChatMigrateTo.md)'\] = \['channel\_id' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionEmpty](messageActionEmpty.md)'\] =   

[$MessageAction](../types/MessageAction.md)\['[messageActionGameScore](messageActionGameScore.md)'\] = \['game\_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \]  

[$MessageAction](../types/MessageAction.md)\['[messageActionHistoryClear](messageActionHistoryClear.md)'\] =   

[$MessageAction](../types/MessageAction.md)\['[messageActionPinMessage](messageActionPinMessage.md)'\] =   

[$Message](../types/Message.md)\['[messageEmpty](messageEmpty.md)'\] = \['id' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityBold](messageEntityBold.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityBotCommand](messageEntityBotCommand.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityCode](messageEntityCode.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityEmail](messageEntityEmail.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityHashtag](messageEntityHashtag.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityItalic](messageEntityItalic.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityMention](messageEntityMention.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityMentionName](messageEntityMentionName.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityPre](messageEntityPre.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityTextUrl](messageEntityTextUrl.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityUnknown](messageEntityUnknown.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageEntity](../types/MessageEntity.md)\['[messageEntityUrl](messageEntityUrl.md)'\] = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \]  

[$MessageFwdHeader](../types/MessageFwdHeader.md)\['[messageFwdHeader](messageFwdHeader.md)'\] = \['from\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'channel\_id' => [int](../types/int.md), 'channel\_post' => [int](../types/int.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaContact](messageMediaContact.md)'\] = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'user\_id' => [int](../types/int.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaDocument](messageMediaDocument.md)'\] = \['document' => [Document](../types/Document.md), 'caption' => [string](../types/string.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaEmpty](messageMediaEmpty.md)'\] =   

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaGame](messageMediaGame.md)'\] = \['game' => [Game](../types/Game.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaGeo](messageMediaGeo.md)'\] = \['geo' => [GeoPoint](../types/GeoPoint.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaPhoto](messageMediaPhoto.md)'\] = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaUnsupported](messageMediaUnsupported.md)'\] =   

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaVenue](messageMediaVenue.md)'\] = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), \]  

[$MessageMedia](../types/MessageMedia.md)\['[messageMediaWebPage](messageMediaWebPage.md)'\] = \['webpage' => [WebPage](../types/WebPage.md), \]  

[$MessageRange](../types/MessageRange.md)\['[messageRange](messageRange.md)'\] = \['min\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \]  

[$Message](../types/Message.md)\['[messageService](messageService.md)'\] = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'to\_id' => [Peer](../types/Peer.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \]  

[$messages\_AffectedHistory](../types/messages\_AffectedHistory.md)\['[messages\_affectedHistory](messages\_affectedHistory.md)'\] = \['pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \]  

[$messages\_AffectedMessages](../types/messages\_AffectedMessages.md)\['[messages\_affectedMessages](messages\_affectedMessages.md)'\] = \['pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$messages\_AllStickers](../types/messages\_AllStickers.md)\['[messages\_allStickers](messages\_allStickers.md)'\] = \['hash' => [int](../types/int.md), 'sets' => [[StickerSet](../types/StickerSet.md)], \]  

[$messages\_AllStickers](../types/messages\_AllStickers.md)\['[messages\_allStickersNotModified](messages\_allStickersNotModified.md)'\] =   

[$messages\_ArchivedStickers](../types/messages\_ArchivedStickers.md)\['[messages\_archivedStickers](messages\_archivedStickers.md)'\] = \['count' => [int](../types/int.md), 'sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], \]  

[$messages\_BotCallbackAnswer](../types/messages\_BotCallbackAnswer.md)\['[messages\_botCallbackAnswer](messages\_botCallbackAnswer.md)'\] = \['alert' => [Bool](../types/Bool.md), 'has\_url' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \]  

[$messages\_BotResults](../types/messages\_BotResults.md)\['[messages\_botResults](messages\_botResults.md)'\] = \['gallery' => [Bool](../types/Bool.md), 'query\_id' => [long](../types/long.md), 'next\_offset' => [string](../types/string.md), 'switch\_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), 'results' => [[BotInlineResult](../types/BotInlineResult.md)], \]  

[$messages\_Messages](../types/messages\_Messages.md)\['[messages\_channelMessages](messages\_channelMessages.md)'\] = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_ChatFull](../types/messages\_ChatFull.md)\['[messages\_chatFull](messages\_chatFull.md)'\] = \['full\_chat' => [ChatFull](../types/ChatFull.md), 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_Chats](../types/messages\_Chats.md)\['[messages\_chats](messages\_chats.md)'\] = \['chats' => [[Chat](../types/Chat.md)], \]  

[$messages\_DhConfig](../types/messages\_DhConfig.md)\['[messages\_dhConfig](messages\_dhConfig.md)'\] = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \]  

[$messages\_DhConfig](../types/messages\_DhConfig.md)\['[messages\_dhConfigNotModified](messages\_dhConfigNotModified.md)'\] = \['random' => [bytes](../types/bytes.md), \]  

[$messages\_Dialogs](../types/messages\_Dialogs.md)\['[messages\_dialogs](messages\_dialogs.md)'\] = \['dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_Dialogs](../types/messages\_Dialogs.md)\['[messages\_dialogsSlice](messages\_dialogsSlice.md)'\] = \['count' => [int](../types/int.md), 'dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_FeaturedStickers](../types/messages\_FeaturedStickers.md)\['[messages\_featuredStickers](messages\_featuredStickers.md)'\] = \['hash' => [int](../types/int.md), 'sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], 'unread' => [[long](../types/long.md)], \]  

[$messages\_FeaturedStickers](../types/messages\_FeaturedStickers.md)\['[messages\_featuredStickersNotModified](messages\_featuredStickersNotModified.md)'\] =   

[$messages\_FoundGifs](../types/messages\_FoundGifs.md)\['[messages\_foundGifs](messages\_foundGifs.md)'\] = \['next\_offset' => [int](../types/int.md), 'results' => [[FoundGif](../types/FoundGif.md)], \]  

[$messages\_HighScores](../types/messages\_HighScores.md)\['[messages\_highScores](messages\_highScores.md)'\] = \['scores' => [[HighScore](../types/HighScore.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_MessageEditData](../types/messages\_MessageEditData.md)\['[messages\_messageEditData](messages\_messageEditData.md)'\] = \['caption' => [Bool](../types/Bool.md), \]  

[$messages\_Messages](../types/messages\_Messages.md)\['[messages\_messages](messages\_messages.md)'\] = \['messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_Messages](../types/messages\_Messages.md)\['[messages\_messagesSlice](messages\_messagesSlice.md)'\] = \['count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$messages\_PeerDialogs](../types/messages\_PeerDialogs.md)\['[messages\_peerDialogs](messages\_peerDialogs.md)'\] = \['dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'state' => [updates\_State](../types/updates\_State.md), \]  

[$messages\_RecentStickers](../types/messages\_RecentStickers.md)\['[messages\_recentStickers](messages\_recentStickers.md)'\] = \['hash' => [int](../types/int.md), 'stickers' => [[Document](../types/Document.md)], \]  

[$messages\_RecentStickers](../types/messages\_RecentStickers.md)\['[messages\_recentStickersNotModified](messages\_recentStickersNotModified.md)'\] =   

[$messages\_SavedGifs](../types/messages\_SavedGifs.md)\['[messages\_savedGifs](messages\_savedGifs.md)'\] = \['hash' => [int](../types/int.md), 'gifs' => [[Document](../types/Document.md)], \]  

[$messages\_SavedGifs](../types/messages\_SavedGifs.md)\['[messages\_savedGifsNotModified](messages\_savedGifsNotModified.md)'\] =   

[$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md)\['[messages\_sentEncryptedFile](messages\_sentEncryptedFile.md)'\] = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \]  

[$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md)\['[messages\_sentEncryptedMessage](messages\_sentEncryptedMessage.md)'\] = \['date' => [int](../types/int.md), \]  

[$messages\_StickerSet](../types/messages\_StickerSet.md)\['[messages\_stickerSet](messages\_stickerSet.md)'\] = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => [[StickerPack](../types/StickerPack.md)], 'documents' => [[Document](../types/Document.md)], \]  

[$messages\_StickerSetInstallResult](../types/messages\_StickerSetInstallResult.md)\['[messages\_stickerSetInstallResultArchive](messages\_stickerSetInstallResultArchive.md)'\] = \['sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], \]  

[$messages\_StickerSetInstallResult](../types/messages\_StickerSetInstallResult.md)\['[messages\_stickerSetInstallResultSuccess](messages\_stickerSetInstallResultSuccess.md)'\] =   

[$messages\_Stickers](../types/messages\_Stickers.md)\['[messages\_stickers](messages\_stickers.md)'\] = \['hash' => [string](../types/string.md), 'stickers' => [[Document](../types/Document.md)], \]  

[$messages\_Stickers](../types/messages\_Stickers.md)\['[messages\_stickersNotModified](messages\_stickersNotModified.md)'\] =   

[$NearestDc](../types/NearestDc.md)\['[nearestDc](nearestDc.md)'\] = \['country' => [string](../types/string.md), 'this\_dc' => [int](../types/int.md), 'nearest\_dc' => [int](../types/int.md), \]  

[$NotifyPeer](../types/NotifyPeer.md)\['[notifyAll](notifyAll.md)'\] =   

[$NotifyPeer](../types/NotifyPeer.md)\['[notifyChats](notifyChats.md)'\] =   

[$NotifyPeer](../types/NotifyPeer.md)\['[notifyPeer](notifyPeer.md)'\] = \['peer' => [Peer](../types/Peer.md), \]  

[$NotifyPeer](../types/NotifyPeer.md)\['[notifyUsers](notifyUsers.md)'\] =   

[$Null](../types/Null.md)\['[null](null.md)'\] =   

[$Peer](../types/Peer.md)\['[peerChannel](peerChannel.md)'\] = \['channel\_id' => [int](../types/int.md), \]  

[$Peer](../types/Peer.md)\['[peerChat](peerChat.md)'\] = \['chat\_id' => [int](../types/int.md), \]  

[$PeerNotifyEvents](../types/PeerNotifyEvents.md)\['[peerNotifyEventsAll](peerNotifyEventsAll.md)'\] =   

[$PeerNotifyEvents](../types/PeerNotifyEvents.md)\['[peerNotifyEventsEmpty](peerNotifyEventsEmpty.md)'\] =   

[$PeerNotifySettings](../types/PeerNotifySettings.md)\['[peerNotifySettings](peerNotifySettings.md)'\] = \['show\_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute\_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \]  

[$PeerNotifySettings](../types/PeerNotifySettings.md)\['[peerNotifySettingsEmpty](peerNotifySettingsEmpty.md)'\] =   

[$PeerSettings](../types/PeerSettings.md)\['[peerSettings](peerSettings.md)'\] = \['report\_spam' => [Bool](../types/Bool.md), \]  

[$Peer](../types/Peer.md)\['[peerUser](peerUser.md)'\] = \['user\_id' => [int](../types/int.md), \]  

[$Photo](../types/Photo.md)\['[photo](photo.md)'\] = \['has\_stickers' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => [[PhotoSize](../types/PhotoSize.md)], \]  

[$PhotoSize](../types/PhotoSize.md)\['[photoCachedSize](photoCachedSize.md)'\] = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]  

[$Photo](../types/Photo.md)\['[photoEmpty](photoEmpty.md)'\] = \['id' => [long](../types/long.md), \]  

[$PhotoSize](../types/PhotoSize.md)\['[photoSize](photoSize.md)'\] = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'size' => [int](../types/int.md), \]  

[$PhotoSize](../types/PhotoSize.md)\['[photoSizeEmpty](photoSizeEmpty.md)'\] = \['type' => [string](../types/string.md), \]  

[$photos\_Photo](../types/photos\_Photo.md)\['[photos\_photo](photos\_photo.md)'\] = \['photo' => [Photo](../types/Photo.md), 'users' => [[User](../types/User.md)], \]  

[$photos\_Photos](../types/photos\_Photos.md)\['[photos\_photos](photos\_photos.md)'\] = \['photos' => [[Photo](../types/Photo.md)], 'users' => [[User](../types/User.md)], \]  

[$photos\_Photos](../types/photos\_Photos.md)\['[photos\_photosSlice](photos\_photosSlice.md)'\] = \['count' => [int](../types/int.md), 'photos' => [[Photo](../types/Photo.md)], 'users' => [[User](../types/User.md)], \]  

[$PrivacyKey](../types/PrivacyKey.md)\['[privacyKeyChatInvite](privacyKeyChatInvite.md)'\] =   

[$PrivacyKey](../types/PrivacyKey.md)\['[privacyKeyStatusTimestamp](privacyKeyStatusTimestamp.md)'\] =   

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueAllowAll](privacyValueAllowAll.md)'\] =   

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueAllowContacts](privacyValueAllowContacts.md)'\] =   

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueAllowUsers](privacyValueAllowUsers.md)'\] = \['users' => [[int](../types/int.md)], \]  

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueDisallowAll](privacyValueDisallowAll.md)'\] =   

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueDisallowContacts](privacyValueDisallowContacts.md)'\] =   

[$PrivacyRule](../types/PrivacyRule.md)\['[privacyValueDisallowUsers](privacyValueDisallowUsers.md)'\] = \['users' => [[int](../types/int.md)], \]  

[$ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)\['[receivedNotifyMessage](receivedNotifyMessage.md)'\] = \['id' => [int](../types/int.md), \]  

[$ReplyMarkup](../types/ReplyMarkup.md)\['[replyInlineMarkup](replyInlineMarkup.md)'\] = \['rows' => [[KeyboardButtonRow](../types/KeyboardButtonRow.md)], \]  

[$ReplyMarkup](../types/ReplyMarkup.md)\['[replyKeyboardForceReply](replyKeyboardForceReply.md)'\] = \['single\_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \]  

[$ReplyMarkup](../types/ReplyMarkup.md)\['[replyKeyboardHide](replyKeyboardHide.md)'\] = \['selective' => [Bool](../types/Bool.md), \]  

[$ReplyMarkup](../types/ReplyMarkup.md)\['[replyKeyboardMarkup](replyKeyboardMarkup.md)'\] = \['resize' => [Bool](../types/Bool.md), 'single\_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => [[KeyboardButtonRow](../types/KeyboardButtonRow.md)], \]  

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageCancelAction](sendMessageCancelAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageChooseContactAction](sendMessageChooseContactAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageGamePlayAction](sendMessageGamePlayAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageGeoLocationAction](sendMessageGeoLocationAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageRecordAudioAction](sendMessageRecordAudioAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageRecordVideoAction](sendMessageRecordVideoAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageTypingAction](sendMessageTypingAction.md)'\] =   

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageUploadAudioAction](sendMessageUploadAudioAction.md)'\] = \['progress' => [int](../types/int.md), \]  

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageUploadDocumentAction](sendMessageUploadDocumentAction.md)'\] = \['progress' => [int](../types/int.md), \]  

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageUploadPhotoAction](sendMessageUploadPhotoAction.md)'\] = \['progress' => [int](../types/int.md), \]  

[$SendMessageAction](../types/SendMessageAction.md)\['[sendMessageUploadVideoAction](sendMessageUploadVideoAction.md)'\] = \['progress' => [int](../types/int.md), \]  

[$StickerPack](../types/StickerPack.md)\['[stickerPack](stickerPack.md)'\] = \['emoticon' => [string](../types/string.md), 'documents' => [[long](../types/long.md)], \]  

[$StickerSet](../types/StickerSet.md)\['[stickerSet](stickerSet.md)'\] = \['installed' => [Bool](../types/Bool.md), 'archived' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'masks' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short\_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \]  

[$StickerSetCovered](../types/StickerSetCovered.md)\['[stickerSetCovered](stickerSetCovered.md)'\] = \['set' => [StickerSet](../types/StickerSet.md), 'cover' => [Document](../types/Document.md), \]  

[$StickerSetCovered](../types/StickerSetCovered.md)\['[stickerSetMultiCovered](stickerSetMultiCovered.md)'\] = \['set' => [StickerSet](../types/StickerSet.md), 'covers' => [[Document](../types/Document.md)], \]  

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileGif](storage\_fileGif.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileJpeg](storage\_fileJpeg.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileMov](storage\_fileMov.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileMp3](storage\_fileMp3.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileMp4](storage\_fileMp4.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_filePartial](storage\_filePartial.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_filePdf](storage\_filePdf.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_filePng](storage\_filePng.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileUnknown](storage\_fileUnknown.md)'\] =   

[$storage\_FileType](../types/storage\_FileType.md)\['[storage\_fileWebp](storage\_fileWebp.md)'\] =   

[$TopPeer](../types/TopPeer.md)\['[topPeer](topPeer.md)'\] = \['peer' => [Peer](../types/Peer.md), 'rating' => [double](../types/double.md), \]  

[$TopPeerCategory](../types/TopPeerCategory.md)\['[topPeerCategoryBotsInline](topPeerCategoryBotsInline.md)'\] =   

[$TopPeerCategory](../types/TopPeerCategory.md)\['[topPeerCategoryBotsPM](topPeerCategoryBotsPM.md)'\] =   

[$TopPeerCategory](../types/TopPeerCategory.md)\['[topPeerCategoryChannels](topPeerCategoryChannels.md)'\] =   

[$TopPeerCategory](../types/TopPeerCategory.md)\['[topPeerCategoryCorrespondents](topPeerCategoryCorrespondents.md)'\] =   

[$TopPeerCategory](../types/TopPeerCategory.md)\['[topPeerCategoryGroups](topPeerCategoryGroups.md)'\] =   

[$TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)\['[topPeerCategoryPeers](topPeerCategoryPeers.md)'\] = \['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'count' => [int](../types/int.md), 'peers' => [[TopPeer](../types/TopPeer.md)], \]  

[$True](../types/True.md)\['[true](true.md)'\] =   

[$Update](../types/Update.md)\['[updateBotCallbackQuery](updateBotCallbackQuery.md)'\] = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'peer' => [Peer](../types/Peer.md), 'msg\_id' => [int](../types/int.md), 'chat\_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game\_short\_name' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateBotInlineQuery](updateBotInlineQuery.md)'\] = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'offset' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateBotInlineSend](updateBotInlineSend.md)'\] = \['user\_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'id' => [string](../types/string.md), 'msg\_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), \]  

[$Update](../types/Update.md)\['[updateChannel](updateChannel.md)'\] = \['channel\_id' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChannelMessageViews](updateChannelMessageViews.md)'\] = \['channel\_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChannelPinnedMessage](updateChannelPinnedMessage.md)'\] = \['channel\_id' => [int](../types/int.md), 'id' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChannelTooLong](updateChannelTooLong.md)'\] = \['channel\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChatAdmins](updateChatAdmins.md)'\] = \['chat\_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChatParticipantAdd](updateChatParticipantAdd.md)'\] = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChatParticipantAdmin](updateChatParticipantAdmin.md)'\] = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'is\_admin' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChatParticipantDelete](updateChatParticipantDelete.md)'\] = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'version' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateChatParticipants](updateChatParticipants.md)'\] = \['participants' => [ChatParticipants](../types/ChatParticipants.md), \]  

[$Update](../types/Update.md)\['[updateChatUserTyping](updateChatUserTyping.md)'\] = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]  

[$Update](../types/Update.md)\['[updateConfig](updateConfig.md)'\] =   

[$Update](../types/Update.md)\['[updateContactLink](updateContactLink.md)'\] = \['user\_id' => [int](../types/int.md), 'my\_link' => [ContactLink](../types/ContactLink.md), 'foreign\_link' => [ContactLink](../types/ContactLink.md), \]  

[$Update](../types/Update.md)\['[updateContactRegistered](updateContactRegistered.md)'\] = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateDcOptions](updateDcOptions.md)'\] = \['dc\_options' => [[DcOption](../types/DcOption.md)], \]  

[$Update](../types/Update.md)\['[updateDeleteChannelMessages](updateDeleteChannelMessages.md)'\] = \['channel\_id' => [int](../types/int.md), 'messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateDeleteMessages](updateDeleteMessages.md)'\] = \['messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateDraftMessage](updateDraftMessage.md)'\] = \['peer' => [Peer](../types/Peer.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \]  

[$Update](../types/Update.md)\['[updateEditChannelMessage](updateEditChannelMessage.md)'\] = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateEditMessage](updateEditMessage.md)'\] = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateEncryptedChatTyping](updateEncryptedChatTyping.md)'\] = \['chat\_id' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateEncryptedMessagesRead](updateEncryptedMessagesRead.md)'\] = \['chat\_id' => [int](../types/int.md), 'max\_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateEncryption](updateEncryption.md)'\] = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateInlineBotCallbackQuery](updateInlineBotCallbackQuery.md)'\] = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'msg\_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'chat\_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game\_short\_name' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateMessageID](updateMessageID.md)'\] = \['id' => [int](../types/int.md), 'random\_id' => [long](../types/long.md), \]  

[$Update](../types/Update.md)\['[updateNewAuthorization](updateNewAuthorization.md)'\] = \['auth\_key\_id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'device' => [string](../types/string.md), 'location' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateNewChannelMessage](updateNewChannelMessage.md)'\] = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateNewEncryptedMessage](updateNewEncryptedMessage.md)'\] = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateNewMessage](updateNewMessage.md)'\] = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateNewStickerSet](updateNewStickerSet.md)'\] = \['stickerset' => [messages\_StickerSet](../types/messages\_StickerSet.md), \]  

[$Update](../types/Update.md)\['[updateNotifySettings](updateNotifySettings.md)'\] = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \]  

[$Update](../types/Update.md)\['[updatePrivacy](updatePrivacy.md)'\] = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => [[PrivacyRule](../types/PrivacyRule.md)], \]  

[$Update](../types/Update.md)\['[updatePtsChanged](updatePtsChanged.md)'\] =   

[$Update](../types/Update.md)\['[updateReadChannelInbox](updateReadChannelInbox.md)'\] = \['channel\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateReadChannelOutbox](updateReadChannelOutbox.md)'\] = \['channel\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateReadFeaturedStickers](updateReadFeaturedStickers.md)'\] =   

[$Update](../types/Update.md)\['[updateReadHistoryInbox](updateReadHistoryInbox.md)'\] = \['peer' => [Peer](../types/Peer.md), 'max\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateReadHistoryOutbox](updateReadHistoryOutbox.md)'\] = \['peer' => [Peer](../types/Peer.md), 'max\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateReadMessagesContents](updateReadMessagesContents.md)'\] = \['messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Update](../types/Update.md)\['[updateRecentStickers](updateRecentStickers.md)'\] =   

[$Update](../types/Update.md)\['[updateSavedGifs](updateSavedGifs.md)'\] =   

[$Update](../types/Update.md)\['[updateServiceNotification](updateServiceNotification.md)'\] = \['type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'popup' => [Bool](../types/Bool.md), \]  

[$Updates](../types/Updates.md)\['[updateShort](updateShort.md)'\] = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \]  

[$Updates](../types/Updates.md)\['[updateShortChatMessage](updateShortChatMessage.md)'\] = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'chat\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]  

[$Updates](../types/Updates.md)\['[updateShortMessage](updateShortMessage.md)'\] = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]  

[$Updates](../types/Updates.md)\['[updateShortSentMessage](updateShortSentMessage.md)'\] = \['out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \]  

[$Update](../types/Update.md)\['[updateStickerSets](updateStickerSets.md)'\] =   

[$Update](../types/Update.md)\['[updateStickerSetsOrder](updateStickerSetsOrder.md)'\] = \['masks' => [Bool](../types/Bool.md), 'order' => [[long](../types/long.md)], \]  

[$Update](../types/Update.md)\['[updateUserBlocked](updateUserBlocked.md)'\] = \['user\_id' => [int](../types/int.md), 'blocked' => [Bool](../types/Bool.md), \]  

[$Update](../types/Update.md)\['[updateUserName](updateUserName.md)'\] = \['user\_id' => [int](../types/int.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'username' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateUserPhone](updateUserPhone.md)'\] = \['user\_id' => [int](../types/int.md), 'phone' => [string](../types/string.md), \]  

[$Update](../types/Update.md)\['[updateUserPhoto](updateUserPhoto.md)'\] = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'previous' => [Bool](../types/Bool.md), \]  

[$Update](../types/Update.md)\['[updateUserStatus](updateUserStatus.md)'\] = \['user\_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \]  

[$Update](../types/Update.md)\['[updateUserTyping](updateUserTyping.md)'\] = \['user\_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \]  

[$Update](../types/Update.md)\['[updateWebPage](updateWebPage.md)'\] = \['webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \]  

[$Updates](../types/Updates.md)\['[updates](updates.md)'\] = \['updates' => [[Update](../types/Update.md)], 'users' => [[User](../types/User.md)], 'chats' => [[Chat](../types/Chat.md)], 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \]  

[$Updates](../types/Updates.md)\['[updatesCombined](updatesCombined.md)'\] = \['updates' => [[Update](../types/Update.md)], 'users' => [[User](../types/User.md)], 'chats' => [[Chat](../types/Chat.md)], 'date' => [int](../types/int.md), 'seq\_start' => [int](../types/int.md), 'seq' => [int](../types/int.md), \]  

[$Updates](../types/Updates.md)\['[updatesTooLong](updatesTooLong.md)'\] =   

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md)\['[updates\_channelDifference](updates\_channelDifference.md)'\] = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'new\_messages' => [[Message](../types/Message.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md)\['[updates\_channelDifferenceEmpty](updates\_channelDifferenceEmpty.md)'\] = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), \]  

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md)\['[updates\_channelDifferenceTooLong](updates\_channelDifferenceTooLong.md)'\] = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top\_message' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \]  

[$updates\_Difference](../types/updates\_Difference.md)\['[updates\_difference](updates\_difference.md)'\] = \['new\_messages' => [[Message](../types/Message.md)], 'new\_encrypted\_messages' => [[EncryptedMessage](../types/EncryptedMessage.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'state' => [updates\_State](../types/updates\_State.md), \]  

[$updates\_Difference](../types/updates\_Difference.md)\['[updates\_differenceEmpty](updates\_differenceEmpty.md)'\] = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \]  

[$updates\_Difference](../types/updates\_Difference.md)\['[updates\_differenceSlice](updates\_differenceSlice.md)'\] = \['new\_messages' => [[Message](../types/Message.md)], 'new\_encrypted\_messages' => [[EncryptedMessage](../types/EncryptedMessage.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'intermediate\_state' => [updates\_State](../types/updates\_State.md), \]  

[$updates\_State](../types/updates\_State.md)\['[updates\_state](updates\_state.md)'\] = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), \]  

[$upload\_File](../types/upload\_File.md)\['[upload\_file](upload\_file.md)'\] = \['type' => [storage\_FileType](../types/storage\_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \]  

[$User](../types/User.md)\['[user](user.md)'\] = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual\_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot\_chat\_history' => [Bool](../types/Bool.md), 'bot\_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'bot\_inline\_geo' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot\_info\_version' => [int](../types/int.md), 'restriction\_reason' => [string](../types/string.md), 'bot\_inline\_placeholder' => [string](../types/string.md), \]  

[$User](../types/User.md)\['[userEmpty](userEmpty.md)'\] = \['id' => [int](../types/int.md), \]  

[$UserFull](../types/UserFull.md)\['[userFull](userFull.md)'\] = \['blocked' => [Bool](../types/Bool.md), 'user' => [User](../types/User.md), 'about' => [string](../types/string.md), 'link' => [contacts\_Link](../types/contacts\_Link.md), 'profile\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'bot\_info' => [BotInfo](../types/BotInfo.md), \]  

[$UserProfilePhoto](../types/UserProfilePhoto.md)\['[userProfilePhoto](userProfilePhoto.md)'\] = \['photo\_id' => [long](../types/long.md), 'photo\_small' => [FileLocation](../types/FileLocation.md), 'photo\_big' => [FileLocation](../types/FileLocation.md), \]  

[$UserProfilePhoto](../types/UserProfilePhoto.md)\['[userProfilePhotoEmpty](userProfilePhotoEmpty.md)'\] =   

[$UserStatus](../types/UserStatus.md)\['[userStatusEmpty](userStatusEmpty.md)'\] =   

[$UserStatus](../types/UserStatus.md)\['[userStatusLastMonth](userStatusLastMonth.md)'\] =   

[$UserStatus](../types/UserStatus.md)\['[userStatusLastWeek](userStatusLastWeek.md)'\] =   

[$UserStatus](../types/UserStatus.md)\['[userStatusOffline](userStatusOffline.md)'\] = \['was\_online' => [int](../types/int.md), \]  

[$UserStatus](../types/UserStatus.md)\['[userStatusOnline](userStatusOnline.md)'\] = \['expires' => [int](../types/int.md), \]  

[$UserStatus](../types/UserStatus.md)\['[userStatusRecently](userStatusRecently.md)'\] =   

[$Vector t](../types/Vector t.md)\['[vector](vector.md)'\] =   

[$WallPaper](../types/WallPaper.md)\['[wallPaper](wallPaper.md)'\] = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => [[PhotoSize](../types/PhotoSize.md)], 'color' => [int](../types/int.md), \]  

[$WallPaper](../types/WallPaper.md)\['[wallPaperSolid](wallPaperSolid.md)'\] = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg\_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \]  

[$WebPage](../types/WebPage.md)\['[webPage](webPage.md)'\] = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display\_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site\_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed\_url' => [string](../types/string.md), 'embed\_type' => [string](../types/string.md), 'embed\_width' => [int](../types/int.md), 'embed\_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), \]  

[$WebPage](../types/WebPage.md)\['[webPageEmpty](webPageEmpty.md)'\] = \['id' => [long](../types/long.md), \]  

[$WebPage](../types/WebPage.md)\['[webPagePending](webPagePending.md)'\] = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \]  

