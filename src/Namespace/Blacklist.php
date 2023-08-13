<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Namespace;

final class Blacklist
{
    public const BLACKLIST = [
        'account.updatePasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update2fa($params), instead (see https://docs.madelineproto.xyz for more info)',
        'account.getPasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update2fa($params), instead (see https://docs.madelineproto.xyz for more info)',
        'messages.receivedQueue' => 'You cannot use this method directly',
        'messages.getDhConfig' => 'You cannot use this method directly, instead use $MadelineProto->getDhConfig();',
        'auth.bindTempAuthKey' => 'You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info',
        'auth.exportAuthorization' => 'You cannot use this method directly, use $MadelineProto->exportAuthorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html',
        'auth.importAuthorization' => 'You cannot use this method directly, use $MadelineProto->importAuthorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html',
        'auth.logOut' => 'You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info)',
        'auth.importBotAuthorization' => 'You cannot use this method directly, use the botLogin method instead (see https://docs.madelineproto.xyz for more info)',
        'auth.sendCode' => 'You cannot use this method directly, use the phoneLogin method instead (see https://docs.madelineproto.xyz for more info)',
        'auth.signIn' => 'You cannot use this method directly, use the completePhoneLogin method instead (see https://docs.madelineproto.xyz for more info)',
        'auth.checkPassword' => 'You cannot use this method directly, use the complete2falogin method instead (see https://docs.madelineproto.xyz for more info)',
        'auth.signUp' => 'You cannot use this method directly, use the completeSignup method instead (see https://docs.madelineproto.xyz for more info)',
        'users.getFullUser' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'users.getUsers' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'channels.getChannels' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'channels.getFullChannel' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'messages.getFullChat' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'contacts.resolveUsername' => 'You cannot use this method directly, use the resolveUsername, getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)',
        'messages.acceptEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats',
        'messages.discardEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats',
        'messages.requestEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats',
        'phone.requestCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls',
        'phone.acceptCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls',
        'phone.confirmCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls',
        'phone.discardCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls',
        'updates.getChannelDifference' => 'You cannot use this method directly, please use the [event handler](https://docs.madelineproto.xyz/docs/UPDATES.html), instead.',
        'updates.getDifference' => 'You cannot use this method directly, please use the [event handler](https://docs.madelineproto.xyz/docs/UPDATES.html), instead.',
        'updates.getState' => 'You cannot use this method directly, please use the [event handler](https://docs.madelineproto.xyz/docs/UPDATES.html), instead.',
        'upload.getCdnFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.getFileHashes' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.getCdnFileHashes' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.reuploadCdnFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.getFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.saveFilePart' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
        'upload.saveBigFilePart' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info',
    ];
}
