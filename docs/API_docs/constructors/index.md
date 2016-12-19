# Constructors  

[$AccountDaysTTL](../types/AccountDaysTTL.md) = \['days' => [int](../types/int.md), \];  

[$account\_Authorizations](../types/account\_Authorizations.md) = \['authorizations' => [[Authorization](../types/Authorization.md)], \];  

[$account\_Password](../types/account\_Password.md) = \['new\_salt' => [bytes](../types/bytes.md), 'email\_unconfirmed\_pattern' => [string](../types/string.md), \];  

[$account\_Password](../types/account\_Password.md) = \['current\_salt' => [bytes](../types/bytes.md), 'new\_salt' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'has\_recovery' => [Bool](../types/Bool.md), 'email\_unconfirmed\_pattern' => [string](../types/string.md), \];  

[$account\_PasswordInputSettings](../types/account\_PasswordInputSettings.md) = \['new\_salt' => [bytes](../types/bytes.md), 'new\_password\_hash' => [bytes](../types/bytes.md), 'hint' => [string](../types/string.md), 'email' => [string](../types/string.md), \];  

[$account\_PasswordSettings](../types/account\_PasswordSettings.md) = \['email' => [string](../types/string.md), \];  

[$account\_PrivacyRules](../types/account\_PrivacyRules.md) = \['rules' => [[PrivacyRule](../types/PrivacyRule.md)], 'users' => [[User](../types/User.md)], \];  

[$auth\_Authorization](../types/auth\_Authorization.md) = \['tmp\_sessions' => [int](../types/int.md), 'user' => [User](../types/User.md), \];  

[$auth\_CheckedPhone](../types/auth\_CheckedPhone.md) = \['phone\_registered' => [Bool](../types/Bool.md), \];  

[$auth\_CodeType](../types/auth\_CodeType.md) = \[\];  

[$auth\_CodeType](../types/auth\_CodeType.md) = \[\];  

[$auth\_CodeType](../types/auth\_CodeType.md) = \[\];  

[$auth\_ExportedAuthorization](../types/auth\_ExportedAuthorization.md) = \['id' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$auth\_PasswordRecovery](../types/auth\_PasswordRecovery.md) = \['email\_pattern' => [string](../types/string.md), \];  

[$auth\_SentCode](../types/auth\_SentCode.md) = \['phone\_registered' => [Bool](../types/Bool.md), 'type' => [auth\_SentCodeType](../types/auth\_SentCodeType.md), 'phone\_code\_hash' => [string](../types/string.md), 'next\_type' => [auth\_CodeType](../types/auth\_CodeType.md), 'timeout' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md) = \['pattern' => [string](../types/string.md), \];  

[$auth\_SentCodeType](../types/auth\_SentCodeType.md) = \['length' => [int](../types/int.md), \];  

[$Authorization](../types/Authorization.md) = \['hash' => [long](../types/long.md), 'device\_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system\_version' => [string](../types/string.md), 'api\_id' => [int](../types/int.md), 'app\_name' => [string](../types/string.md), 'app\_version' => [string](../types/string.md), 'date\_created' => [int](../types/int.md), 'date\_active' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];  

[$Bool](../types/Bool.md) = \[\];  

[$Bool](../types/Bool.md) = \[\];  

[$BotCommand](../types/BotCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$BotInfo](../types/BotInfo.md) = \['user\_id' => [int](../types/int.md), 'description' => [string](../types/string.md), 'commands' => [[BotCommand](../types/BotCommand.md)], \];  

[$BotInlineResult](../types/BotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'send\_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['caption' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineMessage](../types/BotInlineMessage.md) = \['no\_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$BotInlineResult](../types/BotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send\_message' => [BotInlineMessage](../types/BotInlineMessage.md), \];  

[$Chat](../types/Chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'editor' => [Bool](../types/Bool.md), 'moderator' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'democracy' => [Bool](../types/Bool.md), 'signatures' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'username' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'restriction\_reason' => [string](../types/string.md), \];  

[$Chat](../types/Chat.md) = \['broadcast' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), \];  

[$ChatFull](../types/ChatFull.md) = \['can\_view\_participants' => [Bool](../types/Bool.md), 'can\_set\_username' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'about' => [string](../types/string.md), 'participants\_count' => [int](../types/int.md), 'admins\_count' => [int](../types/int.md), 'kicked\_count' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'chat\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported\_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot\_info' => [[BotInfo](../types/BotInfo.md)], 'migrated\_from\_chat\_id' => [int](../types/int.md), 'migrated\_from\_max\_id' => [int](../types/int.md), 'pinned\_msg\_id' => [int](../types/int.md), \];  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md) = \['exclude\_new\_messages' => [Bool](../types/Bool.md), 'ranges' => [[MessageRange](../types/MessageRange.md)], \];  

[$ChannelMessagesFilter](../types/ChannelMessagesFilter.md) = \[\];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), 'kicked\_by' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipant](../types/ChannelParticipant.md) = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$ChannelParticipantRole](../types/ChannelParticipantRole.md) = \[\];  

[$channels\_ChannelParticipant](../types/channels\_ChannelParticipant.md) = \['participant' => [ChannelParticipant](../types/ChannelParticipant.md), 'users' => [[User](../types/User.md)], \];  

[$channels\_ChannelParticipants](../types/channels\_ChannelParticipants.md) = \['count' => [int](../types/int.md), 'participants' => [[ChannelParticipant](../types/ChannelParticipant.md)], 'users' => [[User](../types/User.md)], \];  

[$Chat](../types/Chat.md) = \['creator' => [Bool](../types/Bool.md), 'kicked' => [Bool](../types/Bool.md), 'left' => [Bool](../types/Bool.md), 'admins\_enabled' => [Bool](../types/Bool.md), 'admin' => [Bool](../types/Bool.md), 'deactivated' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), 'migrated\_to' => [InputChannel](../types/InputChannel.md), \];  

[$Chat](../types/Chat.md) = \['id' => [int](../types/int.md), \];  

[$Chat](../types/Chat.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), \];  

[$ChatFull](../types/ChatFull.md) = \['id' => [int](../types/int.md), 'participants' => [ChatParticipants](../types/ChatParticipants.md), 'chat\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'exported\_invite' => [ExportedChatInvite](../types/ExportedChatInvite.md), 'bot\_info' => [[BotInfo](../types/BotInfo.md)], \];  

[$ChatInvite](../types/ChatInvite.md) = \['channel' => [Bool](../types/Bool.md), 'broadcast' => [Bool](../types/Bool.md), 'public' => [Bool](../types/Bool.md), 'megagroup' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'photo' => [ChatPhoto](../types/ChatPhoto.md), 'participants\_count' => [int](../types/int.md), 'participants' => [[User](../types/User.md)], \];  

[$ChatInvite](../types/ChatInvite.md) = \['chat' => [Chat](../types/Chat.md), \];  

[$ExportedChatInvite](../types/ExportedChatInvite.md) = \[\];  

[$ExportedChatInvite](../types/ExportedChatInvite.md) = \['link' => [string](../types/string.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ChatParticipant](../types/ChatParticipant.md) = \['user\_id' => [int](../types/int.md), \];  

[$ChatParticipants](../types/ChatParticipants.md) = \['chat\_id' => [int](../types/int.md), 'participants' => [[ChatParticipant](../types/ChatParticipant.md)], 'version' => [int](../types/int.md), \];  

[$ChatParticipants](../types/ChatParticipants.md) = \['chat\_id' => [int](../types/int.md), 'self\_participant' => [ChatParticipant](../types/ChatParticipant.md), \];  

[$ChatPhoto](../types/ChatPhoto.md) = \['photo\_small' => [FileLocation](../types/FileLocation.md), 'photo\_big' => [FileLocation](../types/FileLocation.md), \];  

[$ChatPhoto](../types/ChatPhoto.md) = \[\];  

[$Config](../types/Config.md) = \['date' => [int](../types/int.md), 'expires' => [int](../types/int.md), 'test\_mode' => [Bool](../types/Bool.md), 'this\_dc' => [int](../types/int.md), 'dc\_options' => [[DcOption](../types/DcOption.md)], 'chat\_size\_max' => [int](../types/int.md), 'megagroup\_size\_max' => [int](../types/int.md), 'forwarded\_count\_max' => [int](../types/int.md), 'online\_update\_period\_ms' => [int](../types/int.md), 'offline\_blur\_timeout\_ms' => [int](../types/int.md), 'offline\_idle\_timeout\_ms' => [int](../types/int.md), 'online\_cloud\_timeout\_ms' => [int](../types/int.md), 'notify\_cloud\_delay\_ms' => [int](../types/int.md), 'notify\_default\_delay\_ms' => [int](../types/int.md), 'chat\_big\_size' => [int](../types/int.md), 'push\_chat\_period\_ms' => [int](../types/int.md), 'push\_chat\_limit' => [int](../types/int.md), 'saved\_gifs\_limit' => [int](../types/int.md), 'edit\_time\_limit' => [int](../types/int.md), 'rating\_e\_decay' => [int](../types/int.md), 'stickers\_recent\_limit' => [int](../types/int.md), 'tmp\_sessions' => [int](../types/int.md), 'disabled\_features' => [[DisabledFeature](../types/DisabledFeature.md)], \];  

[$Contact](../types/Contact.md) = \['user\_id' => [int](../types/int.md), 'mutual' => [Bool](../types/Bool.md), \];  

[$ContactBlocked](../types/ContactBlocked.md) = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactLink](../types/ContactLink.md) = \[\];  

[$ContactStatus](../types/ContactStatus.md) = \['user\_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$contacts\_Blocked](../types/contacts\_Blocked.md) = \['blocked' => [[ContactBlocked](../types/ContactBlocked.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_Blocked](../types/contacts\_Blocked.md) = \['count' => [int](../types/int.md), 'blocked' => [[ContactBlocked](../types/ContactBlocked.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_Contacts](../types/contacts\_Contacts.md) = \['contacts' => [[Contact](../types/Contact.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_Contacts](../types/contacts\_Contacts.md) = \[\];  

[$contacts\_Found](../types/contacts\_Found.md) = \['results' => [[Peer](../types/Peer.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_ImportedContacts](../types/contacts\_ImportedContacts.md) = \['imported' => [[ImportedContact](../types/ImportedContact.md)], 'retry\_contacts' => [[long](../types/long.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_Link](../types/contacts\_Link.md) = \['my\_link' => [ContactLink](../types/ContactLink.md), 'foreign\_link' => [ContactLink](../types/ContactLink.md), 'user' => [User](../types/User.md), \];  

[$contacts\_ResolvedPeer](../types/contacts\_ResolvedPeer.md) = \['peer' => [Peer](../types/Peer.md), 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_TopPeers](../types/contacts\_TopPeers.md) = \['categories' => [[TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$contacts\_TopPeers](../types/contacts\_TopPeers.md) = \[\];  

[$DcOption](../types/DcOption.md) = \['ipv6' => [Bool](../types/Bool.md), 'media\_only' => [Bool](../types/Bool.md), 'tcpo\_only' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'ip\_address' => [string](../types/string.md), 'port' => [int](../types/int.md), \];  

[$Dialog](../types/Dialog.md) = \['peer' => [Peer](../types/Peer.md), 'top\_message' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'pts' => [int](../types/int.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$DisabledFeature](../types/DisabledFeature.md) = \['feature' => [string](../types/string.md), 'description' => [string](../types/string.md), \];  

[$Document](../types/Document.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'mime\_type' => [string](../types/string.md), 'size' => [int](../types/int.md), 'thumb' => [PhotoSize](../types/PhotoSize.md), 'dc\_id' => [int](../types/int.md), 'version' => [int](../types/int.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \[\];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['voice' => [Bool](../types/Bool.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'waveform' => [bytes](../types/bytes.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['file\_name' => [string](../types/string.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \[\];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['mask' => [Bool](../types/Bool.md), 'alt' => [string](../types/string.md), 'stickerset' => [InputStickerSet](../types/InputStickerSet.md), 'mask\_coords' => [MaskCoords](../types/MaskCoords.md), \];  

[$DocumentAttribute](../types/DocumentAttribute.md) = \['duration' => [int](../types/int.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$Document](../types/Document.md) = \['id' => [long](../types/long.md), \];  

[$DraftMessage](../types/DraftMessage.md) = \['no\_webpage' => [Bool](../types/Bool.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'date' => [int](../types/int.md), \];  

[$DraftMessage](../types/DraftMessage.md) = \[\];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), 'g\_a\_or\_b' => [bytes](../types/bytes.md), 'key\_fingerprint' => [long](../types/long.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), 'g\_a' => [bytes](../types/bytes.md), \];  

[$EncryptedChat](../types/EncryptedChat.md) = \['id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'admin\_id' => [int](../types/int.md), 'participant\_id' => [int](../types/int.md), \];  

[$EncryptedFile](../types/EncryptedFile.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'size' => [int](../types/int.md), 'dc\_id' => [int](../types/int.md), 'key\_fingerprint' => [int](../types/int.md), \];  

[$EncryptedFile](../types/EncryptedFile.md) = \[\];  

[$EncryptedMessage](../types/EncryptedMessage.md) = \['random\_id' => [long](../types/long.md), 'chat\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$EncryptedMessage](../types/EncryptedMessage.md) = \['random\_id' => [long](../types/long.md), 'chat\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$Error](../types/Error.md) = \['code' => [int](../types/int.md), 'text' => [string](../types/string.md), \];  

[$ExportedMessageLink](../types/ExportedMessageLink.md) = \['link' => [string](../types/string.md), \];  

[$FileLocation](../types/FileLocation.md) = \['dc\_id' => [int](../types/int.md), 'volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$FileLocation](../types/FileLocation.md) = \['volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$FoundGif](../types/FoundGif.md) = \['url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), \];  

[$FoundGif](../types/FoundGif.md) = \['url' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$Game](../types/Game.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'short\_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'document' => [Document](../types/Document.md), \];  

[$GeoPoint](../types/GeoPoint.md) = \['long' => [double](../types/double.md), 'lat' => [double](../types/double.md), \];  

[$GeoPoint](../types/GeoPoint.md) = \[\];  

[$help\_AppChangelog](../types/help\_AppChangelog.md) = \['text' => [string](../types/string.md), \];  

[$help\_AppChangelog](../types/help\_AppChangelog.md) = \[\];  

[$help\_AppUpdate](../types/help\_AppUpdate.md) = \['id' => [int](../types/int.md), 'critical' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), 'text' => [string](../types/string.md), \];  

[$help\_InviteText](../types/help\_InviteText.md) = \['message' => [string](../types/string.md), \];  

[$help\_AppUpdate](../types/help\_AppUpdate.md) = \[\];  

[$help\_Support](../types/help\_Support.md) = \['phone\_number' => [string](../types/string.md), 'user' => [User](../types/User.md), \];  

[$help\_TermsOfService](../types/help\_TermsOfService.md) = \['text' => [string](../types/string.md), \];  

[$HighScore](../types/HighScore.md) = \['pos' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];  

[$ImportedContact](../types/ImportedContact.md) = \['user\_id' => [int](../types/int.md), 'client\_id' => [long](../types/long.md), \];  

[$InlineBotSwitchPM](../types/InlineBotSwitchPM.md) = \['text' => [string](../types/string.md), 'start\_param' => [string](../types/string.md), \];  

[$InputAppEvent](../types/InputAppEvent.md) = \['time' => [double](../types/double.md), 'type' => [string](../types/string.md), 'peer' => [long](../types/long.md), 'data' => [string](../types/string.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessageID](../types/InputBotInlineMessageID.md) = \['dc\_id' => [int](../types/int.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['caption' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineMessage](../types/InputBotInlineMessage.md) = \['no\_webpage' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'url' => [string](../types/string.md), 'thumb\_url' => [string](../types/string.md), 'content\_url' => [string](../types/string.md), 'content\_type' => [string](../types/string.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document' => [InputDocument](../types/InputDocument.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'short\_name' => [string](../types/string.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputBotInlineResult](../types/InputBotInlineResult.md) = \['id' => [string](../types/string.md), 'type' => [string](../types/string.md), 'photo' => [InputPhoto](../types/InputPhoto.md), 'send\_message' => [InputBotInlineMessage](../types/InputBotInlineMessage.md), \];  

[$InputChannel](../types/InputChannel.md) = \['channel\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputChannel](../types/InputChannel.md) = \[\];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \[\];  

[$InputChatPhoto](../types/InputChatPhoto.md) = \['file' => [InputFile](../types/InputFile.md), \];  

[$InputDocument](../types/InputDocument.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputDocument](../types/InputDocument.md) = \[\];  

[$InputFileLocation](../types/InputFileLocation.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'version' => [int](../types/int.md), \];  

[$InputEncryptedChat](../types/InputEncryptedChat.md) = \['chat\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'key\_fingerprint' => [int](../types/int.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \[\];  

[$InputFileLocation](../types/InputFileLocation.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputEncryptedFile](../types/InputEncryptedFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'md5\_checksum' => [string](../types/string.md), 'key\_fingerprint' => [int](../types/int.md), \];  

[$InputFile](../types/InputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), 'md5\_checksum' => [string](../types/string.md), \];  

[$InputFile](../types/InputFile.md) = \['id' => [long](../types/long.md), 'parts' => [int](../types/int.md), 'name' => [string](../types/string.md), \];  

[$InputFileLocation](../types/InputFileLocation.md) = \['volume\_id' => [long](../types/long.md), 'local\_id' => [int](../types/int.md), 'secret' => [long](../types/long.md), \];  

[$InputGame](../types/InputGame.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputGame](../types/InputGame.md) = \['bot\_id' => [InputUser](../types/InputUser.md), 'short\_name' => [string](../types/string.md), \];  

[$InputGeoPoint](../types/InputGeoPoint.md) = \['lat' => [double](../types/double.md), 'long' => [double](../types/double.md), \];  

[$InputGeoPoint](../types/InputGeoPoint.md) = \[\];  

[$InputMedia](../types/InputMedia.md) = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputDocument](../types/InputDocument.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \[\];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputGame](../types/InputGame.md), \];  

[$InputMedia](../types/InputMedia.md) = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'q' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['id' => [InputPhoto](../types/InputPhoto.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['url' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'mime\_type' => [string](../types/string.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \];  

[$InputMedia](../types/InputMedia.md) = \['file' => [InputFile](../types/InputFile.md), 'thumb' => [InputFile](../types/InputFile.md), 'mime\_type' => [string](../types/string.md), 'attributes' => [[DocumentAttribute](../types/DocumentAttribute.md)], 'caption' => [string](../types/string.md), 'stickers' => [[InputDocument](../types/InputDocument.md)], \];  

[$InputMedia](../types/InputMedia.md) = \['geo\_point' => [InputGeoPoint](../types/InputGeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user\_id' => [InputUser](../types/InputUser.md), \];  

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

[$InputPeer](../types/InputPeer.md) = \['channel\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputPeer](../types/InputPeer.md) = \['chat\_id' => [int](../types/int.md), \];  

[$InputPeer](../types/InputPeer.md) = \[\];  

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md) = \[\];  

[$InputPeerNotifyEvents](../types/InputPeerNotifyEvents.md) = \[\];  

[$InputPeerNotifySettings](../types/InputPeerNotifySettings.md) = \['show\_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute\_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$InputPeer](../types/InputPeer.md) = \[\];  

[$InputPeer](../types/InputPeer.md) = \['user\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputContact](../types/InputContact.md) = \['client\_id' => [long](../types/long.md), 'phone' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), \];  

[$InputPhoto](../types/InputPhoto.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputPhoto](../types/InputPhoto.md) = \[\];  

[$InputPrivacyKey](../types/InputPrivacyKey.md) = \[\];  

[$InputPrivacyKey](../types/InputPrivacyKey.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \['users' => [[InputUser](../types/InputUser.md)], \];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \[\];  

[$InputPrivacyRule](../types/InputPrivacyRule.md) = \['users' => [[InputUser](../types/InputUser.md)], \];  

[$ReportReason](../types/ReportReason.md) = \['text' => [string](../types/string.md), \];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$ReportReason](../types/ReportReason.md) = \[\];  

[$InputStickerSet](../types/InputStickerSet.md) = \[\];  

[$InputStickerSet](../types/InputStickerSet.md) = \['id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputStickerSet](../types/InputStickerSet.md) = \['short\_name' => [string](../types/string.md), \];  

[$InputStickeredMedia](../types/InputStickeredMedia.md) = \['id' => [InputDocument](../types/InputDocument.md), \];  

[$InputStickeredMedia](../types/InputStickeredMedia.md) = \['id' => [InputPhoto](../types/InputPhoto.md), \];  

[$InputUser](../types/InputUser.md) = \['user\_id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), \];  

[$InputUser](../types/InputUser.md) = \[\];  

[$InputUser](../types/InputUser.md) = \[\];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), 'data' => [bytes](../types/bytes.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), \];  

[$KeyboardButtonRow](../types/KeyboardButtonRow.md) = \['buttons' => [[KeyboardButton](../types/KeyboardButton.md)], \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['same\_peer' => [Bool](../types/Bool.md), 'text' => [string](../types/string.md), 'query' => [string](../types/string.md), \];  

[$KeyboardButton](../types/KeyboardButton.md) = \['text' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$MaskCoords](../types/MaskCoords.md) = \['n' => [int](../types/int.md), 'x' => [double](../types/double.md), 'y' => [double](../types/double.md), 'zoom' => [double](../types/double.md), \];  

[$Message](../types/Message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'to\_id' => [Peer](../types/Peer.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'reply\_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], 'views' => [int](../types/int.md), 'edit\_date' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), 'chat\_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['users' => [[int](../types/int.md)], \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), 'users' => [[int](../types/int.md)], \];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$MessageAction](../types/MessageAction.md) = \['user\_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['photo' => [Photo](../types/Photo.md), \];  

[$MessageAction](../types/MessageAction.md) = \['title' => [string](../types/string.md), \];  

[$MessageAction](../types/MessageAction.md) = \['inviter\_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \['channel\_id' => [int](../types/int.md), \];  

[$MessageAction](../types/MessageAction.md) = \[\];  

[$MessageAction](../types/MessageAction.md) = \['game\_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];  

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

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'language' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'url' => [string](../types/string.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageEntity](../types/MessageEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), \];  

[$MessageFwdHeader](../types/MessageFwdHeader.md) = \['from\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'channel\_id' => [int](../types/int.md), 'channel\_post' => [int](../types/int.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['phone\_number' => [string](../types/string.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'user\_id' => [int](../types/int.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['document' => [Document](../types/Document.md), 'caption' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \[\];  

[$MessageMedia](../types/MessageMedia.md) = \['game' => [Game](../types/Game.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['photo' => [Photo](../types/Photo.md), 'caption' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \[\];  

[$MessageMedia](../types/MessageMedia.md) = \['geo' => [GeoPoint](../types/GeoPoint.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'venue\_id' => [string](../types/string.md), \];  

[$MessageMedia](../types/MessageMedia.md) = \['webpage' => [WebPage](../types/WebPage.md), \];  

[$MessageRange](../types/MessageRange.md) = \['min\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \];  

[$Message](../types/Message.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'post' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'to\_id' => [Peer](../types/Peer.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'action' => [MessageAction](../types/MessageAction.md), \];  

[$messages\_AffectedHistory](../types/messages\_AffectedHistory.md) = \['pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'offset' => [int](../types/int.md), \];  

[$messages\_AffectedMessages](../types/messages\_AffectedMessages.md) = \['pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$messages\_AllStickers](../types/messages\_AllStickers.md) = \['hash' => [int](../types/int.md), 'sets' => [[StickerSet](../types/StickerSet.md)], \];  

[$messages\_AllStickers](../types/messages\_AllStickers.md) = \[\];  

[$messages\_ArchivedStickers](../types/messages\_ArchivedStickers.md) = \['count' => [int](../types/int.md), 'sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], \];  

[$messages\_BotCallbackAnswer](../types/messages\_BotCallbackAnswer.md) = \['alert' => [Bool](../types/Bool.md), 'has\_url' => [Bool](../types/Bool.md), 'message' => [string](../types/string.md), 'url' => [string](../types/string.md), \];  

[$messages\_BotResults](../types/messages\_BotResults.md) = \['gallery' => [Bool](../types/Bool.md), 'query\_id' => [long](../types/long.md), 'next\_offset' => [string](../types/string.md), 'switch\_pm' => [InlineBotSwitchPM](../types/InlineBotSwitchPM.md), 'results' => [[BotInlineResult](../types/BotInlineResult.md)], \];  

[$messages\_Messages](../types/messages\_Messages.md) = \['pts' => [int](../types/int.md), 'count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_ChatFull](../types/messages\_ChatFull.md) = \['full\_chat' => [ChatFull](../types/ChatFull.md), 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_Chats](../types/messages\_Chats.md) = \['chats' => [[Chat](../types/Chat.md)], \];  

[$messages\_DhConfig](../types/messages\_DhConfig.md) = \['g' => [int](../types/int.md), 'p' => [bytes](../types/bytes.md), 'version' => [int](../types/int.md), 'random' => [bytes](../types/bytes.md), \];  

[$messages\_DhConfig](../types/messages\_DhConfig.md) = \['random' => [bytes](../types/bytes.md), \];  

[$messages\_Dialogs](../types/messages\_Dialogs.md) = \['dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_Dialogs](../types/messages\_Dialogs.md) = \['count' => [int](../types/int.md), 'dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_FeaturedStickers](../types/messages\_FeaturedStickers.md) = \['hash' => [int](../types/int.md), 'sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], 'unread' => [[long](../types/long.md)], \];  

[$messages\_FeaturedStickers](../types/messages\_FeaturedStickers.md) = \[\];  

[$messages\_FoundGifs](../types/messages\_FoundGifs.md) = \['next\_offset' => [int](../types/int.md), 'results' => [[FoundGif](../types/FoundGif.md)], \];  

[$messages\_HighScores](../types/messages\_HighScores.md) = \['scores' => [[HighScore](../types/HighScore.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_MessageEditData](../types/messages\_MessageEditData.md) = \['caption' => [Bool](../types/Bool.md), \];  

[$messages\_Messages](../types/messages\_Messages.md) = \['messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_Messages](../types/messages\_Messages.md) = \['count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$messages\_PeerDialogs](../types/messages\_PeerDialogs.md) = \['dialogs' => [[Dialog](../types/Dialog.md)], 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'state' => [updates\_State](../types/updates\_State.md), \];  

[$messages\_RecentStickers](../types/messages\_RecentStickers.md) = \['hash' => [int](../types/int.md), 'stickers' => [[Document](../types/Document.md)], \];  

[$messages\_RecentStickers](../types/messages\_RecentStickers.md) = \[\];  

[$messages\_SavedGifs](../types/messages\_SavedGifs.md) = \['hash' => [int](../types/int.md), 'gifs' => [[Document](../types/Document.md)], \];  

[$messages\_SavedGifs](../types/messages\_SavedGifs.md) = \[\];  

[$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md) = \['date' => [int](../types/int.md), 'file' => [EncryptedFile](../types/EncryptedFile.md), \];  

[$messages\_SentEncryptedMessage](../types/messages\_SentEncryptedMessage.md) = \['date' => [int](../types/int.md), \];  

[$messages\_StickerSet](../types/messages\_StickerSet.md) = \['set' => [StickerSet](../types/StickerSet.md), 'packs' => [[StickerPack](../types/StickerPack.md)], 'documents' => [[Document](../types/Document.md)], \];  

[$messages\_StickerSetInstallResult](../types/messages\_StickerSetInstallResult.md) = \['sets' => [[StickerSetCovered](../types/StickerSetCovered.md)], \];  

[$messages\_StickerSetInstallResult](../types/messages\_StickerSetInstallResult.md) = \[\];  

[$messages\_Stickers](../types/messages\_Stickers.md) = \['hash' => [string](../types/string.md), 'stickers' => [[Document](../types/Document.md)], \];  

[$messages\_Stickers](../types/messages\_Stickers.md) = \[\];  

[$NearestDc](../types/NearestDc.md) = \['country' => [string](../types/string.md), 'this\_dc' => [int](../types/int.md), 'nearest\_dc' => [int](../types/int.md), \];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$NotifyPeer](../types/NotifyPeer.md) = \['peer' => [Peer](../types/Peer.md), \];  

[$NotifyPeer](../types/NotifyPeer.md) = \[\];  

[$Null](../types/Null.md) = \[\];  

[$Peer](../types/Peer.md) = \['channel\_id' => [int](../types/int.md), \];  

[$Peer](../types/Peer.md) = \['chat\_id' => [int](../types/int.md), \];  

[$PeerNotifyEvents](../types/PeerNotifyEvents.md) = \[\];  

[$PeerNotifyEvents](../types/PeerNotifyEvents.md) = \[\];  

[$PeerNotifySettings](../types/PeerNotifySettings.md) = \['show\_previews' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'mute\_until' => [int](../types/int.md), 'sound' => [string](../types/string.md), \];  

[$PeerNotifySettings](../types/PeerNotifySettings.md) = \[\];  

[$PeerSettings](../types/PeerSettings.md) = \['report\_spam' => [Bool](../types/Bool.md), \];  

[$Peer](../types/Peer.md) = \['user\_id' => [int](../types/int.md), \];  

[$Photo](../types/Photo.md) = \['has\_stickers' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'date' => [int](../types/int.md), 'sizes' => [[PhotoSize](../types/PhotoSize.md)], \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$Photo](../types/Photo.md) = \['id' => [long](../types/long.md), \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), 'location' => [FileLocation](../types/FileLocation.md), 'w' => [int](../types/int.md), 'h' => [int](../types/int.md), 'size' => [int](../types/int.md), \];  

[$PhotoSize](../types/PhotoSize.md) = \['type' => [string](../types/string.md), \];  

[$photos\_Photo](../types/photos\_Photo.md) = \['photo' => [Photo](../types/Photo.md), 'users' => [[User](../types/User.md)], \];  

[$photos\_Photos](../types/photos\_Photos.md) = \['photos' => [[Photo](../types/Photo.md)], 'users' => [[User](../types/User.md)], \];  

[$photos\_Photos](../types/photos\_Photos.md) = \['count' => [int](../types/int.md), 'photos' => [[Photo](../types/Photo.md)], 'users' => [[User](../types/User.md)], \];  

[$PrivacyKey](../types/PrivacyKey.md) = \[\];  

[$PrivacyKey](../types/PrivacyKey.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \['users' => [[int](../types/int.md)], \];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \[\];  

[$PrivacyRule](../types/PrivacyRule.md) = \['users' => [[int](../types/int.md)], \];  

[$ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md) = \['id' => [int](../types/int.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['rows' => [[KeyboardButtonRow](../types/KeyboardButtonRow.md)], \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['single\_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['selective' => [Bool](../types/Bool.md), \];  

[$ReplyMarkup](../types/ReplyMarkup.md) = \['resize' => [Bool](../types/Bool.md), 'single\_use' => [Bool](../types/Bool.md), 'selective' => [Bool](../types/Bool.md), 'rows' => [[KeyboardButtonRow](../types/KeyboardButtonRow.md)], \];  

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

[$StickerPack](../types/StickerPack.md) = \['emoticon' => [string](../types/string.md), 'documents' => [[long](../types/long.md)], \];  

[$StickerSet](../types/StickerSet.md) = \['installed' => [Bool](../types/Bool.md), 'archived' => [Bool](../types/Bool.md), 'official' => [Bool](../types/Bool.md), 'masks' => [Bool](../types/Bool.md), 'id' => [long](../types/long.md), 'access\_hash' => [long](../types/long.md), 'title' => [string](../types/string.md), 'short\_name' => [string](../types/string.md), 'count' => [int](../types/int.md), 'hash' => [int](../types/int.md), \];  

[$StickerSetCovered](../types/StickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'cover' => [Document](../types/Document.md), \];  

[$StickerSetCovered](../types/StickerSetCovered.md) = \['set' => [StickerSet](../types/StickerSet.md), 'covers' => [[Document](../types/Document.md)], \];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$storage\_FileType](../types/storage\_FileType.md) = \[\];  

[$TopPeer](../types/TopPeer.md) = \['peer' => [Peer](../types/Peer.md), 'rating' => [double](../types/double.md), \];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategory](../types/TopPeerCategory.md) = \[\];  

[$TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md) = \['category' => [TopPeerCategory](../types/TopPeerCategory.md), 'count' => [int](../types/int.md), 'peers' => [[TopPeer](../types/TopPeer.md)], \];  

[$True](../types/True.md) = \[\];  

[$Update](../types/Update.md) = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'peer' => [Peer](../types/Peer.md), 'msg\_id' => [int](../types/int.md), 'chat\_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game\_short\_name' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'offset' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'query' => [string](../types/string.md), 'geo' => [GeoPoint](../types/GeoPoint.md), 'id' => [string](../types/string.md), 'msg\_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'id' => [int](../types/int.md), 'views' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'enabled' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'inviter\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'is\_admin' => [Bool](../types/Bool.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'version' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['participants' => [ChatParticipants](../types/ChatParticipants.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'my\_link' => [ContactLink](../types/ContactLink.md), 'foreign\_link' => [ContactLink](../types/ContactLink.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['dc\_options' => [[DcOption](../types/DcOption.md)], \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'draft' => [DraftMessage](../types/DraftMessage.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat\_id' => [int](../types/int.md), 'max\_date' => [int](../types/int.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['chat' => [EncryptedChat](../types/EncryptedChat.md), 'date' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['query\_id' => [long](../types/long.md), 'user\_id' => [int](../types/int.md), 'msg\_id' => [InputBotInlineMessageID](../types/InputBotInlineMessageID.md), 'chat\_instance' => [long](../types/long.md), 'data' => [bytes](../types/bytes.md), 'game\_short\_name' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['id' => [int](../types/int.md), 'random\_id' => [long](../types/long.md), \];  

[$Update](../types/Update.md) = \['auth\_key\_id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'device' => [string](../types/string.md), 'location' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [EncryptedMessage](../types/EncryptedMessage.md), 'qts' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['message' => [Message](../types/Message.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['stickerset' => [messages\_StickerSet](../types/messages\_StickerSet.md), \];  

[$Update](../types/Update.md) = \['peer' => [NotifyPeer](../types/NotifyPeer.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), \];  

[$Update](../types/Update.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => [[PrivacyRule](../types/PrivacyRule.md)], \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['channel\_id' => [int](../types/int.md), 'max\_id' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'max\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['peer' => [Peer](../types/Peer.md), 'max\_id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \['messages' => [[int](../types/int.md)], 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['type' => [string](../types/string.md), 'message' => [string](../types/string.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'popup' => [Bool](../types/Bool.md), \];  

[$Updates](../types/Updates.md) = \['update' => [Update](../types/Update.md), 'date' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'from\_id' => [int](../types/int.md), 'chat\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'mentioned' => [Bool](../types/Bool.md), 'media\_unread' => [Bool](../types/Bool.md), 'silent' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'user\_id' => [int](../types/int.md), 'message' => [string](../types/string.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'fwd\_from' => [MessageFwdHeader](../types/MessageFwdHeader.md), 'via\_bot\_id' => [int](../types/int.md), 'reply\_to\_msg\_id' => [int](../types/int.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \];  

[$Updates](../types/Updates.md) = \['out' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), 'date' => [int](../types/int.md), 'media' => [MessageMedia](../types/MessageMedia.md), 'entities' => [[MessageEntity](../types/MessageEntity.md)], \];  

[$Update](../types/Update.md) = \[\];  

[$Update](../types/Update.md) = \['masks' => [Bool](../types/Bool.md), 'order' => [[long](../types/long.md)], \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'blocked' => [Bool](../types/Bool.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'username' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'phone' => [string](../types/string.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'date' => [int](../types/int.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'previous' => [Bool](../types/Bool.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];  

[$Update](../types/Update.md) = \['user\_id' => [int](../types/int.md), 'action' => [SendMessageAction](../types/SendMessageAction.md), \];  

[$Update](../types/Update.md) = \['webpage' => [WebPage](../types/WebPage.md), 'pts' => [int](../types/int.md), 'pts\_count' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['updates' => [[Update](../types/Update.md)], 'users' => [[User](../types/User.md)], 'chats' => [[Chat](../types/Chat.md)], 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \['updates' => [[Update](../types/Update.md)], 'users' => [[User](../types/User.md)], 'chats' => [[Chat](../types/Chat.md)], 'date' => [int](../types/int.md), 'seq\_start' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$Updates](../types/Updates.md) = \[\];  

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'new\_messages' => [[Message](../types/Message.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), \];  

[$updates\_ChannelDifference](../types/updates\_ChannelDifference.md) = \['final' => [Bool](../types/Bool.md), 'pts' => [int](../types/int.md), 'timeout' => [int](../types/int.md), 'top\_message' => [int](../types/int.md), 'read\_inbox\_max\_id' => [int](../types/int.md), 'read\_outbox\_max\_id' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), 'messages' => [[Message](../types/Message.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], \];  

[$updates\_Difference](../types/updates\_Difference.md) = \['new\_messages' => [[Message](../types/Message.md)], 'new\_encrypted\_messages' => [[EncryptedMessage](../types/EncryptedMessage.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'state' => [updates\_State](../types/updates\_State.md), \];  

[$updates\_Difference](../types/updates\_Difference.md) = \['date' => [int](../types/int.md), 'seq' => [int](../types/int.md), \];  

[$updates\_Difference](../types/updates\_Difference.md) = \['new\_messages' => [[Message](../types/Message.md)], 'new\_encrypted\_messages' => [[EncryptedMessage](../types/EncryptedMessage.md)], 'other\_updates' => [[Update](../types/Update.md)], 'chats' => [[Chat](../types/Chat.md)], 'users' => [[User](../types/User.md)], 'intermediate\_state' => [updates\_State](../types/updates\_State.md), \];  

[$updates\_State](../types/updates\_State.md) = \['pts' => [int](../types/int.md), 'qts' => [int](../types/int.md), 'date' => [int](../types/int.md), 'seq' => [int](../types/int.md), 'unread\_count' => [int](../types/int.md), \];  

[$upload\_File](../types/upload\_File.md) = \['type' => [storage\_FileType](../types/storage\_FileType.md), 'mtime' => [int](../types/int.md), 'bytes' => [bytes](../types/bytes.md), \];  

[$User](../types/User.md) = \['self' => [Bool](../types/Bool.md), 'contact' => [Bool](../types/Bool.md), 'mutual\_contact' => [Bool](../types/Bool.md), 'deleted' => [Bool](../types/Bool.md), 'bot' => [Bool](../types/Bool.md), 'bot\_chat\_history' => [Bool](../types/Bool.md), 'bot\_nochats' => [Bool](../types/Bool.md), 'verified' => [Bool](../types/Bool.md), 'restricted' => [Bool](../types/Bool.md), 'min' => [Bool](../types/Bool.md), 'bot\_inline\_geo' => [Bool](../types/Bool.md), 'id' => [int](../types/int.md), 'access\_hash' => [long](../types/long.md), 'first\_name' => [string](../types/string.md), 'last\_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone' => [string](../types/string.md), 'photo' => [UserProfilePhoto](../types/UserProfilePhoto.md), 'status' => [UserStatus](../types/UserStatus.md), 'bot\_info\_version' => [int](../types/int.md), 'restriction\_reason' => [string](../types/string.md), 'bot\_inline\_placeholder' => [string](../types/string.md), \];  

[$User](../types/User.md) = \['id' => [int](../types/int.md), \];  

[$UserFull](../types/UserFull.md) = \['blocked' => [Bool](../types/Bool.md), 'user' => [User](../types/User.md), 'about' => [string](../types/string.md), 'link' => [contacts\_Link](../types/contacts\_Link.md), 'profile\_photo' => [Photo](../types/Photo.md), 'notify\_settings' => [PeerNotifySettings](../types/PeerNotifySettings.md), 'bot\_info' => [BotInfo](../types/BotInfo.md), \];  

[$UserProfilePhoto](../types/UserProfilePhoto.md) = \['photo\_id' => [long](../types/long.md), 'photo\_small' => [FileLocation](../types/FileLocation.md), 'photo\_big' => [FileLocation](../types/FileLocation.md), \];  

[$UserProfilePhoto](../types/UserProfilePhoto.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$UserStatus](../types/UserStatus.md) = \['was\_online' => [int](../types/int.md), \];  

[$UserStatus](../types/UserStatus.md) = \['expires' => [int](../types/int.md), \];  

[$UserStatus](../types/UserStatus.md) = \[\];  

[$Vector t](../types/Vector t.md) = \[\];  

[$WallPaper](../types/WallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'sizes' => [[PhotoSize](../types/PhotoSize.md)], 'color' => [int](../types/int.md), \];  

[$WallPaper](../types/WallPaper.md) = \['id' => [int](../types/int.md), 'title' => [string](../types/string.md), 'bg\_color' => [int](../types/int.md), 'color' => [int](../types/int.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), 'url' => [string](../types/string.md), 'display\_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site\_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [Photo](../types/Photo.md), 'embed\_url' => [string](../types/string.md), 'embed\_type' => [string](../types/string.md), 'embed\_width' => [int](../types/int.md), 'embed\_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'document' => [Document](../types/Document.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), \];  

[$WebPage](../types/WebPage.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), \];  

