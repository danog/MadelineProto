<?php
/**
 * This file is automatic generated by build_docs.php file
 * and is used only for autocomplete in multiple IDE
 * dont modify manually
 */

namespace danog\MadelineProto;

interface auth
{    /**
     * @param array params [
     *               string phone_number,
     *              ]
     * @return auth_CheckedPhone
     */
    public function checkPhone(array $params);
    /**
     * @param array params [
     *               boolean allow_flashcall,
     *               string phone_number,
     *               Bool current_number,
     *               int api_id,
     *               string api_hash,
     *              ]
     * @return auth_SentCode
     */
    public function sendCode(array $params);
    /**
     * @param array params [
     *               string phone_number,
     *               string phone_code_hash,
     *               string phone_code,
     *               string first_name,
     *               string last_name,
     *              ]
     * @return auth_Authorization
     */
    public function signUp(array $params);
    /**
     * @param array params [
     *               string phone_number,
     *               string phone_code_hash,
     *               string phone_code,
     *              ]
     * @return auth_Authorization
     */
    public function signIn(array $params);
    /**
     * @return Bool
     */
    public function logOut();
    /**
     * @return Bool
     */
    public function resetAuthorizations();
    /**
     * @param array params [
     *               string phone_numbers,
     *               string message,
     *              ]
     * @return Bool
     */
    public function sendInvites(array $params);
    /**
     * @param array params [
     *               int dc_id,
     *              ]
     * @return auth_ExportedAuthorization
     */
    public function exportAuthorization(array $params);
    /**
     * @param array params [
     *               int id,
     *               bytes bytes,
     *              ]
     * @return auth_Authorization
     */
    public function importAuthorization(array $params);
    /**
     * @param array params [
     *               long perm_auth_key_id,
     *               long nonce,
     *               int expires_at,
     *               bytes encrypted_message,
     *              ]
     * @return Bool
     */
    public function bindTempAuthKey(array $params);
    /**
     * @param array params [
     *               int api_id,
     *               string api_hash,
     *               string bot_auth_token,
     *              ]
     * @return auth_Authorization
     */
    public function importBotAuthorization(array $params);
    /**
     * @param array params [
     *               bytes password_hash,
     *              ]
     * @return auth_Authorization
     */
    public function checkPassword(array $params);
    /**
     * @return auth_PasswordRecovery
     */
    public function requestPasswordRecovery();
    /**
     * @param array params [
     *               string code,
     *              ]
     * @return auth_Authorization
     */
    public function recoverPassword(array $params);
    /**
     * @param array params [
     *               string phone_number,
     *               string phone_code_hash,
     *              ]
     * @return auth_SentCode
     */
    public function resendCode(array $params);
    /**
     * @param array params [
     *               string phone_number,
     *               string phone_code_hash,
     *              ]
     * @return Bool
     */
    public function cancelCode(array $params);
    /**
     * @param array params [
     *               long except_auth_keys,
     *              ]
     * @return Bool
     */
    public function dropTempAuthKeys(array $params);

}

interface account
{    /**
     * @param array params [
     *               int token_type,
     *               string token,
     *              ]
     * @return Bool
     */
    public function registerDevice(array $params);
    /**
     * @param array params [
     *               int token_type,
     *               string token,
     *              ]
     * @return Bool
     */
    public function unregisterDevice(array $params);
    /**
     * @param array params [
     *               InputNotifyPeer peer,
     *               InputPeerNotifySettings settings,
     *              ]
     * @return Bool
     */
    public function updateNotifySettings(array $params);
    /**
     * @param array params [
     *               InputNotifyPeer peer,
     *              ]
     * @return PeerNotifySettings
     */
    public function getNotifySettings(array $params);
    /**
     * @return Bool
     */
    public function resetNotifySettings();
    /**
     * @param array params [
     *               string first_name,
     *               string last_name,
     *               string about,
     *              ]
     * @return User
     */
    public function updateProfile(array $params);
    /**
     * @param array params [
     *               Bool offline,
     *              ]
     * @return Bool
     */
    public function updateStatus(array $params);
    /**
     * @return Vector_of_WallPaper
     */
    public function getWallPapers();
    /**
     * @param array params [
     *               InputPeer peer,
     *               ReportReason reason,
     *              ]
     * @return Bool
     */
    public function reportPeer(array $params);
    /**
     * @param array params [
     *               string username,
     *              ]
     * @return Bool
     */
    public function checkUsername(array $params);
    /**
     * @param array params [
     *               string username,
     *              ]
     * @return User
     */
    public function updateUsername(array $params);
    /**
     * @param array params [
     *               InputPrivacyKey key,
     *              ]
     * @return account_PrivacyRules
     */
    public function getPrivacy(array $params);
    /**
     * @param array params [
     *               InputPrivacyKey key,
     *               InputPrivacyRule rules,
     *              ]
     * @return account_PrivacyRules
     */
    public function setPrivacy(array $params);
    /**
     * @param array params [
     *               string reason,
     *              ]
     * @return Bool
     */
    public function deleteAccount(array $params);
    /**
     * @return AccountDaysTTL
     */
    public function getAccountTTL();
    /**
     * @param array params [
     *               AccountDaysTTL ttl,
     *              ]
     * @return Bool
     */
    public function setAccountTTL(array $params);
    /**
     * @param array params [
     *               boolean allow_flashcall,
     *               string phone_number,
     *               Bool current_number,
     *              ]
     * @return auth_SentCode
     */
    public function sendChangePhoneCode(array $params);
    /**
     * @param array params [
     *               string phone_number,
     *               string phone_code_hash,
     *               string phone_code,
     *              ]
     * @return User
     */
    public function changePhone(array $params);
    /**
     * @param array params [
     *               int period,
     *              ]
     * @return Bool
     */
    public function updateDeviceLocked(array $params);
    /**
     * @return account_Authorizations
     */
    public function getAuthorizations();
    /**
     * @param array params [
     *               long hash,
     *              ]
     * @return Bool
     */
    public function resetAuthorization(array $params);
    /**
     * @return account_Password
     */
    public function getPassword();
    /**
     * @param array params [
     *               bytes current_password_hash,
     *              ]
     * @return account_PasswordSettings
     */
    public function getPasswordSettings(array $params);
    /**
     * @param array params [
     *               bytes current_password_hash,
     *               account_PasswordInputSettings new_settings,
     *              ]
     * @return Bool
     */
    public function updatePasswordSettings(array $params);
    /**
     * @param array params [
     *               boolean allow_flashcall,
     *               string hash,
     *               Bool current_number,
     *              ]
     * @return auth_SentCode
     */
    public function sendConfirmPhoneCode(array $params);
    /**
     * @param array params [
     *               string phone_code_hash,
     *               string phone_code,
     *              ]
     * @return Bool
     */
    public function confirmPhone(array $params);

}

interface users
{    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return Vector_of_User
     */
    public function getUsers(array $params);
    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return UserFull
     */
    public function getFullUser(array $params);

}

interface contacts
{    /**
     * @return Vector_of_ContactStatus
     */
    public function getStatuses();
    /**
     * @param array params [
     *               string hash,
     *              ]
     * @return contacts_Contacts
     */
    public function getContacts(array $params);
    /**
     * @param array params [
     *               InputContact contacts,
     *               Bool replace,
     *              ]
     * @return contacts_ImportedContacts
     */
    public function importContacts(array $params);
    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return contacts_Link
     */
    public function deleteContact(array $params);
    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return Bool
     */
    public function deleteContacts(array $params);
    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return Bool
     */
    public function block(array $params);
    /**
     * @param array params [
     *               InputUser id,
     *              ]
     * @return Bool
     */
    public function unblock(array $params);
    /**
     * @param array params [
     *               int offset,
     *               int limit,
     *              ]
     * @return contacts_Blocked
     */
    public function getBlocked(array $params);
    /**
     * @return Vector_of_int
     */
    public function exportCard();
    /**
     * @param array params [
     *               int export_card,
     *              ]
     * @return User
     */
    public function importCard(array $params);
    /**
     * @param array params [
     *               string q,
     *               int limit,
     *              ]
     * @return contacts_Found
     */
    public function search(array $params);
    /**
     * @param array params [
     *               string username,
     *              ]
     * @return contacts_ResolvedPeer
     */
    public function resolveUsername(array $params);
    /**
     * @param array params [
     *               boolean correspondents,
     *               boolean bots_pm,
     *               boolean bots_inline,
     *               boolean groups,
     *               boolean channels,
     *               int offset,
     *               int limit,
     *               int hash,
     *              ]
     * @return contacts_TopPeers
     */
    public function getTopPeers(array $params);
    /**
     * @param array params [
     *               TopPeerCategory category,
     *               InputPeer peer,
     *              ]
     * @return Bool
     */
    public function resetTopPeerRating(array $params);

}

interface messages
{    /**
     * @param array params [
     *               int id,
     *              ]
     * @return messages_Messages
     */
    public function getMessages(array $params);
    /**
     * @param array params [
     *               int offset_date,
     *               int offset_id,
     *               InputPeer offset_peer,
     *               int limit,
     *              ]
     * @return messages_Dialogs
     */
    public function getDialogs(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int offset_id,
     *               int offset_date,
     *               int add_offset,
     *               int limit,
     *               int max_id,
     *               int min_id,
     *              ]
     * @return messages_Messages
     */
    public function getHistory(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               string q,
     *               MessagesFilter filter,
     *               int min_date,
     *               int max_date,
     *               int offset,
     *               int max_id,
     *               int limit,
     *              ]
     * @return messages_Messages
     */
    public function search(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int max_id,
     *              ]
     * @return messages_AffectedMessages
     */
    public function readHistory(array $params);
    /**
     * @param array params [
     *               boolean just_clear,
     *               InputPeer peer,
     *               int max_id,
     *              ]
     * @return messages_AffectedHistory
     */
    public function deleteHistory(array $params);
    /**
     * @param array params [
     *               int id,
     *              ]
     * @return messages_AffectedMessages
     */
    public function deleteMessages(array $params);
    /**
     * @param array params [
     *               int max_id,
     *              ]
     * @return Vector_of_ReceivedNotifyMessage
     */
    public function receivedMessages(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               SendMessageAction action,
     *              ]
     * @return Bool
     */
    public function setTyping(array $params);
    /**
     * @param array params [
     *               boolean no_webpage,
     *               boolean silent,
     *               boolean background,
     *               boolean clear_draft,
     *               InputPeer peer,
     *               int reply_to_msg_id,
     *               string message,
     *               ReplyMarkup reply_markup,
     *               MessageEntity entities,
     *              ]
     * @return Updates
     */
    public function sendMessage(array $params);
    /**
     * @param array params [
     *               boolean silent,
     *               boolean background,
     *               boolean clear_draft,
     *               InputPeer peer,
     *               int reply_to_msg_id,
     *               InputMedia media,
     *               ReplyMarkup reply_markup,
     *              ]
     * @return Updates
     */
    public function sendMedia(array $params);
    /**
     * @param array params [
     *               boolean silent,
     *               boolean background,
     *               boolean with_my_score,
     *               InputPeer from_peer,
     *               int id,
     *               InputPeer to_peer,
     *              ]
     * @return Updates
     */
    public function forwardMessages(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *              ]
     * @return Bool
     */
    public function reportSpam(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *              ]
     * @return Bool
     */
    public function hideReportSpam(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *              ]
     * @return PeerSettings
     */
    public function getPeerSettings(array $params);
    /**
     * @param array params [
     *               int id,
     *              ]
     * @return messages_Chats
     */
    public function getChats(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *              ]
     * @return messages_ChatFull
     */
    public function getFullChat(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               string title,
     *              ]
     * @return Updates
     */
    public function editChatTitle(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               InputChatPhoto photo,
     *              ]
     * @return Updates
     */
    public function editChatPhoto(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               InputUser user_id,
     *               int fwd_limit,
     *              ]
     * @return Updates
     */
    public function addChatUser(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               InputUser user_id,
     *              ]
     * @return Updates
     */
    public function deleteChatUser(array $params);
    /**
     * @param array params [
     *               InputUser users,
     *               string title,
     *              ]
     * @return Updates
     */
    public function createChat(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int id,
     *              ]
     * @return Updates
     */
    public function forwardMessage(array $params);
    /**
     * @param array params [
     *               int version,
     *               int random_length,
     *              ]
     * @return messages_DhConfig
     */
    public function getDhConfig(array $params);
    /**
     * @param array params [
     *               InputUser user_id,
     *               bytes g_a,
     *              ]
     * @return EncryptedChat
     */
    public function requestEncryption(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               bytes g_b,
     *               long key_fingerprint,
     *              ]
     * @return EncryptedChat
     */
    public function acceptEncryption(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *              ]
     * @return Bool
     */
    public function discardEncryption(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               Bool typing,
     *              ]
     * @return Bool
     */
    public function setEncryptedTyping(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               int max_date,
     *              ]
     * @return Bool
     */
    public function readEncryptedHistory(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               bytes data,
     *              ]
     * @return messages_SentEncryptedMessage
     */
    public function sendEncrypted(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               bytes data,
     *               InputEncryptedFile file,
     *              ]
     * @return messages_SentEncryptedMessage
     */
    public function sendEncryptedFile(array $params);
    /**
     * @param array params [
     *               InputEncryptedChat peer,
     *               bytes data,
     *              ]
     * @return messages_SentEncryptedMessage
     */
    public function sendEncryptedService(array $params);
    /**
     * @param array params [
     *               int max_qts,
     *              ]
     * @return Vector_of_long
     */
    public function receivedQueue(array $params);
    /**
     * @param array params [
     *               int id,
     *              ]
     * @return messages_AffectedMessages
     */
    public function readMessageContents(array $params);
    /**
     * @param array params [
     *               int hash,
     *              ]
     * @return messages_AllStickers
     */
    public function getAllStickers(array $params);
    /**
     * @param array params [
     *               string message,
     *              ]
     * @return MessageMedia
     */
    public function getWebPagePreview(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *              ]
     * @return ExportedChatInvite
     */
    public function exportChatInvite(array $params);
    /**
     * @param array params [
     *               string hash,
     *              ]
     * @return ChatInvite
     */
    public function checkChatInvite(array $params);
    /**
     * @param array params [
     *               string hash,
     *              ]
     * @return Updates
     */
    public function importChatInvite(array $params);
    /**
     * @param array params [
     *               InputStickerSet stickerset,
     *              ]
     * @return messages_StickerSet
     */
    public function getStickerSet(array $params);
    /**
     * @param array params [
     *               InputStickerSet stickerset,
     *               Bool archived,
     *              ]
     * @return messages_StickerSetInstallResult
     */
    public function installStickerSet(array $params);
    /**
     * @param array params [
     *               InputStickerSet stickerset,
     *              ]
     * @return Bool
     */
    public function uninstallStickerSet(array $params);
    /**
     * @param array params [
     *               InputUser bot,
     *               InputPeer peer,
     *               string start_param,
     *              ]
     * @return Updates
     */
    public function startBot(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int id,
     *               Bool increment,
     *              ]
     * @return Vector_of_int
     */
    public function getMessagesViews(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               Bool enabled,
     *              ]
     * @return Updates
     */
    public function toggleChatAdmins(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *               InputUser user_id,
     *               Bool is_admin,
     *              ]
     * @return Bool
     */
    public function editChatAdmin(array $params);
    /**
     * @param array params [
     *               int chat_id,
     *              ]
     * @return Updates
     */
    public function migrateChat(array $params);
    /**
     * @param array params [
     *               string q,
     *               int offset_date,
     *               InputPeer offset_peer,
     *               int offset_id,
     *               int limit,
     *              ]
     * @return messages_Messages
     */
    public function searchGlobal(array $params);
    /**
     * @param array params [
     *               boolean masks,
     *               long order,
     *              ]
     * @return Bool
     */
    public function reorderStickerSets(array $params);
    /**
     * @param array params [
     *               bytes sha256,
     *               int size,
     *               string mime_type,
     *              ]
     * @return Document
     */
    public function getDocumentByHash(array $params);
    /**
     * @param array params [
     *               string q,
     *               int offset,
     *              ]
     * @return messages_FoundGifs
     */
    public function searchGifs(array $params);
    /**
     * @param array params [
     *               int hash,
     *              ]
     * @return messages_SavedGifs
     */
    public function getSavedGifs(array $params);
    /**
     * @param array params [
     *               InputDocument id,
     *               Bool unsave,
     *              ]
     * @return Bool
     */
    public function saveGif(array $params);
    /**
     * @param array params [
     *               InputUser bot,
     *               InputPeer peer,
     *               InputGeoPoint geo_point,
     *               string query,
     *               string offset,
     *              ]
     * @return messages_BotResults
     */
    public function getInlineBotResults(array $params);
    /**
     * @param array params [
     *               boolean gallery,
     *               boolean private,
     *               long query_id,
     *               InputBotInlineResult results,
     *               int cache_time,
     *               string next_offset,
     *               InlineBotSwitchPM switch_pm,
     *              ]
     * @return Bool
     */
    public function setInlineBotResults(array $params);
    /**
     * @param array params [
     *               boolean silent,
     *               boolean background,
     *               boolean clear_draft,
     *               InputPeer peer,
     *               int reply_to_msg_id,
     *               long query_id,
     *               string id,
     *              ]
     * @return Updates
     */
    public function sendInlineBotResult(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int id,
     *              ]
     * @return messages_MessageEditData
     */
    public function getMessageEditData(array $params);
    /**
     * @param array params [
     *               boolean no_webpage,
     *               InputPeer peer,
     *               int id,
     *               string message,
     *               ReplyMarkup reply_markup,
     *               MessageEntity entities,
     *              ]
     * @return Updates
     */
    public function editMessage(array $params);
    /**
     * @param array params [
     *               boolean no_webpage,
     *               InputBotInlineMessageID id,
     *               string message,
     *               ReplyMarkup reply_markup,
     *               MessageEntity entities,
     *              ]
     * @return Bool
     */
    public function editInlineBotMessage(array $params);
    /**
     * @param array params [
     *               boolean game,
     *               InputPeer peer,
     *               int msg_id,
     *               bytes data,
     *              ]
     * @return messages_BotCallbackAnswer
     */
    public function getBotCallbackAnswer(array $params);
    /**
     * @param array params [
     *               boolean alert,
     *               long query_id,
     *               string message,
     *               string url,
     *              ]
     * @return Bool
     */
    public function setBotCallbackAnswer(array $params);
    /**
     * @param array params [
     *               InputPeer peers,
     *              ]
     * @return messages_PeerDialogs
     */
    public function getPeerDialogs(array $params);
    /**
     * @param array params [
     *               boolean no_webpage,
     *               int reply_to_msg_id,
     *               InputPeer peer,
     *               string message,
     *               MessageEntity entities,
     *              ]
     * @return Bool
     */
    public function saveDraft(array $params);
    /**
     * @return Updates
     */
    public function getAllDrafts();
    /**
     * @param array params [
     *               int hash,
     *              ]
     * @return messages_FeaturedStickers
     */
    public function getFeaturedStickers(array $params);
    /**
     * @param array params [
     *               long id,
     *              ]
     * @return Bool
     */
    public function readFeaturedStickers(array $params);
    /**
     * @param array params [
     *               boolean attached,
     *               int hash,
     *              ]
     * @return messages_RecentStickers
     */
    public function getRecentStickers(array $params);
    /**
     * @param array params [
     *               boolean attached,
     *               InputDocument id,
     *               Bool unsave,
     *              ]
     * @return Bool
     */
    public function saveRecentSticker(array $params);
    /**
     * @param array params [
     *               boolean attached,
     *              ]
     * @return Bool
     */
    public function clearRecentStickers(array $params);
    /**
     * @param array params [
     *               boolean masks,
     *               long offset_id,
     *               int limit,
     *              ]
     * @return messages_ArchivedStickers
     */
    public function getArchivedStickers(array $params);
    /**
     * @param array params [
     *               int hash,
     *              ]
     * @return messages_AllStickers
     */
    public function getMaskStickers(array $params);
    /**
     * @param array params [
     *               InputStickeredMedia media,
     *              ]
     * @return Vector_of_StickerSetCovered
     */
    public function getAttachedStickers(array $params);
    /**
     * @param array params [
     *               boolean edit_message,
     *               InputPeer peer,
     *               int id,
     *               InputUser user_id,
     *               int score,
     *              ]
     * @return Updates
     */
    public function setGameScore(array $params);
    /**
     * @param array params [
     *               boolean edit_message,
     *               InputBotInlineMessageID id,
     *               InputUser user_id,
     *               int score,
     *              ]
     * @return Bool
     */
    public function setInlineGameScore(array $params);
    /**
     * @param array params [
     *               InputPeer peer,
     *               int id,
     *               InputUser user_id,
     *              ]
     * @return messages_HighScores
     */
    public function getGameHighScores(array $params);
    /**
     * @param array params [
     *               InputBotInlineMessageID id,
     *               InputUser user_id,
     *              ]
     * @return messages_HighScores
     */
    public function getInlineGameHighScores(array $params);

}

interface updates
{    /**
     * @return updates_State
     */
    public function getState();
    /**
     * @param array params [
     *               int pts,
     *               int date,
     *               int qts,
     *              ]
     * @return updates_Difference
     */
    public function getDifference(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               ChannelMessagesFilter filter,
     *               int pts,
     *               int limit,
     *              ]
     * @return updates_ChannelDifference
     */
    public function getChannelDifference(array $params);

}

interface photos
{    /**
     * @param array params [
     *               InputPhoto id,
     *              ]
     * @return UserProfilePhoto
     */
    public function updateProfilePhoto(array $params);
    /**
     * @param array params [
     *               InputFile file,
     *              ]
     * @return photos_Photo
     */
    public function uploadProfilePhoto(array $params);
    /**
     * @param array params [
     *               InputPhoto id,
     *              ]
     * @return Vector_of_long
     */
    public function deletePhotos(array $params);
    /**
     * @param array params [
     *               InputUser user_id,
     *               int offset,
     *               long max_id,
     *               int limit,
     *              ]
     * @return photos_Photos
     */
    public function getUserPhotos(array $params);

}

interface upload
{    /**
     * @param array params [
     *               long file_id,
     *               int file_part,
     *               bytes bytes,
     *              ]
     * @return Bool
     */
    public function saveFilePart(array $params);
    /**
     * @param array params [
     *               InputFileLocation location,
     *               int offset,
     *               int limit,
     *              ]
     * @return upload_File
     */
    public function getFile(array $params);
    /**
     * @param array params [
     *               long file_id,
     *               int file_part,
     *               int file_total_parts,
     *               bytes bytes,
     *              ]
     * @return Bool
     */
    public function saveBigFilePart(array $params);

}

interface help
{    /**
     * @return Config
     */
    public function getConfig();
    /**
     * @return NearestDc
     */
    public function getNearestDc();
    /**
     * @return help_AppUpdate
     */
    public function getAppUpdate();
    /**
     * @param array params [
     *               InputAppEvent events,
     *              ]
     * @return Bool
     */
    public function saveAppLog(array $params);
    /**
     * @return help_InviteText
     */
    public function getInviteText();
    /**
     * @return help_Support
     */
    public function getSupport();
    /**
     * @return help_AppChangelog
     */
    public function getAppChangelog();
    /**
     * @return help_TermsOfService
     */
    public function getTermsOfService();

}

interface channels
{    /**
     * @param array params [
     *               InputChannel channel,
     *               int max_id,
     *              ]
     * @return Bool
     */
    public function readHistory(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               int id,
     *              ]
     * @return messages_AffectedMessages
     */
    public function deleteMessages(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser user_id,
     *              ]
     * @return messages_AffectedHistory
     */
    public function deleteUserHistory(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser user_id,
     *               int id,
     *              ]
     * @return Bool
     */
    public function reportSpam(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               int id,
     *              ]
     * @return messages_Messages
     */
    public function getMessages(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               ChannelParticipantsFilter filter,
     *               int offset,
     *               int limit,
     *              ]
     * @return channels_ChannelParticipants
     */
    public function getParticipants(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser user_id,
     *              ]
     * @return channels_ChannelParticipant
     */
    public function getParticipant(array $params);
    /**
     * @param array params [
     *               InputChannel id,
     *              ]
     * @return messages_Chats
     */
    public function getChannels(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *              ]
     * @return messages_ChatFull
     */
    public function getFullChannel(array $params);
    /**
     * @param array params [
     *               boolean broadcast,
     *               boolean megagroup,
     *               string title,
     *               string about,
     *              ]
     * @return Updates
     */
    public function createChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               string about,
     *              ]
     * @return Bool
     */
    public function editAbout(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser user_id,
     *               ChannelParticipantRole role,
     *              ]
     * @return Updates
     */
    public function editAdmin(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               string title,
     *              ]
     * @return Updates
     */
    public function editTitle(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputChatPhoto photo,
     *              ]
     * @return Updates
     */
    public function editPhoto(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               string username,
     *              ]
     * @return Bool
     */
    public function checkUsername(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               string username,
     *              ]
     * @return Bool
     */
    public function updateUsername(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *              ]
     * @return Updates
     */
    public function joinChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *              ]
     * @return Updates
     */
    public function leaveChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser users,
     *              ]
     * @return Updates
     */
    public function inviteToChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               InputUser user_id,
     *               Bool kicked,
     *              ]
     * @return Updates
     */
    public function kickFromChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *              ]
     * @return ExportedChatInvite
     */
    public function exportInvite(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *              ]
     * @return Updates
     */
    public function deleteChannel(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               Bool enabled,
     *              ]
     * @return Updates
     */
    public function toggleInvites(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               int id,
     *              ]
     * @return ExportedMessageLink
     */
    public function exportMessageLink(array $params);
    /**
     * @param array params [
     *               InputChannel channel,
     *               Bool enabled,
     *              ]
     * @return Updates
     */
    public function toggleSignatures(array $params);
    /**
     * @param array params [
     *               boolean silent,
     *               InputChannel channel,
     *               int id,
     *              ]
     * @return Updates
     */
    public function updatePinnedMessage(array $params);
    /**
     * @return messages_Chats
     */
    public function getAdminedPublicChannels();

}
