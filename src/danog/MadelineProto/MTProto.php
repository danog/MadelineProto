<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages all of the mtproto stuff.
 */
class MTProto extends \Volatile
{
    use \danog\Serializable;
    use \danog\MadelineProto\MTProtoTools\AckHandler;
    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
    use \danog\MadelineProto\MTProtoTools\CallHandler;
    use \danog\MadelineProto\MTProtoTools\Crypt;
    use \danog\MadelineProto\MTProtoTools\MessageHandler;
    use \danog\MadelineProto\MTProtoTools\MsgIdHandler;
    use \danog\MadelineProto\MTProtoTools\PeerHandler;
    use \danog\MadelineProto\MTProtoTools\ResponseHandler;
    use \danog\MadelineProto\MTProtoTools\SaltHandler;
    use \danog\MadelineProto\MTProtoTools\SeqNoHandler;
    use \danog\MadelineProto\MTProtoTools\UpdateHandler;
    use \danog\MadelineProto\MTProtoTools\Files;
    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
    use \danog\MadelineProto\SecretChats\MessageHandler;
    use \danog\MadelineProto\SecretChats\ResponseHandler;
    use \danog\MadelineProto\SecretChats\SeqNoHandler;
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\TL\Conversion\BotAPI;
    use \danog\MadelineProto\TL\Conversion\BotAPIFiles;
    use \danog\MadelineProto\TL\Conversion\Extension;
    use \danog\MadelineProto\TL\Conversion\TD;
    use \danog\MadelineProto\Tools;
    use \danog\MadelineProto\VoIP\AuthKeyHandler;
    use \danog\MadelineProto\Wrappers\DialogHandler;
    use \danog\MadelineProto\Wrappers\Login;

    const V = 71;

    const NOT_LOGGED_IN = 0;
    const WAITING_CODE = 1;
    const WAITING_SIGNUP = -1;
    const WAITING_PASSWORD = 2;
    const LOGGED_IN = 3;
    const DISALLOWED_METHODS = [
        'auth.logOut'                 => 'You cannot use this method directly, use the logout method instead (see https://daniil.it/MadelineProto for more info)',
        'auth.importBotAuthorization' => 'You cannot use this method directly, use the bot_login method instead (see https://daniil.it/MadelineProto for more info)',
        'auth.sendCode'               => 'You cannot use this method directly, use the phone_login method instead (see https://daniil.it/MadelineProto for more info)',
        'auth.signIn'                 => 'You cannot use this method directly, use the complete_phone_login method instead (see https://daniil.it/MadelineProto for more info)',
        'auth.signUp'                 => 'You cannot use this method directly, use the complete_signup method instead (see https://daniil.it/MadelineProto for more info)',
        'users.getFullUser'           => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)',
        'channels.getFullChannel'     => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)',
        'messages.getFullChat'        => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)',
        'channels.getParticipants'    => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)',
        'contacts.resolveUsername'    => 'You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)',

        'messages.acceptEncryption'  => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling secret chats',
        'messages.discardEncryption' => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling secret chats',
        'messages.requestEncryption' => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling secret chats',

        'phone.requestCall' => 'You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls',
        'phone.acceptCall'  => 'You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls',
        'phone.confirmCall' => 'You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls',
        'phone.discardCall' => 'You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls',

        'updates.getChannelDifference' => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling updates',
        'updates.getDifference'        => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling updates',
        'updates.getState'             => 'You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling updates',

        'upload.getCdnFile'            => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',
        'upload.getCdnFileHashes'      => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',
        'upload.reuploadCdnFile'       => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',
        'upload.getFile'               => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',
        'upload.saveFilePart'          => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',
        'upload.saveBigFilePart'       => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info',

    ];
    const BAD_MSG_ERROR_CODES = [
        16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the â€œcorrectâ€ msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)',
        17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)',
        18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)',
        19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)',
        20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not',
        32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)',
        33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)',
        34 => 'an even msg_seqno expected (irrelevant message), but odd received',
        35 => 'odd msg_seqno expected (relevant message), but even received',
        48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)',
        64 => 'invalid container.',
    ];
    const MSGS_INFO_FLAGS = [
        1   => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)',
        2   => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)',
        3   => 'message not received (msg_id too high; however, the other party has certainly not received it yet)',
        4   => 'message received (note that this response is also at the same time a receipt acknowledgment)',
        8   => ' and message already acknowledged',
        16  => ' and message not requiring acknowledgment',
        32  => ' and RPC query contained in message being processed or processing already complete',
        64  => ' and content-related response to message already generated',
        128 => ' and other party knows for a fact that message is already received',
    ];
    const REQUESTED = 0;
    const ACCEPTED = 1;
    const CONFIRMED = 2;
    const READY = 3;
    const JSON_EMOJIS = '["\\ud83d\\ude09","\\ud83d\\ude0d","\\ud83d\\ude1b","\\ud83d\\ude2d","\\ud83d\\ude31","\\ud83d\\ude21","\\ud83d\\ude0e","\\ud83d\\ude34","\\ud83d\\ude35","\\ud83d\\ude08","\\ud83d\\ude2c","\\ud83d\\ude07","\\ud83d\\ude0f","\\ud83d\\udc6e","\\ud83d\\udc77","\\ud83d\\udc82","\\ud83d\\udc76","\\ud83d\\udc68","\\ud83d\\udc69","\\ud83d\\udc74","\\ud83d\\udc75","\\ud83d\\ude3b","\\ud83d\\ude3d","\\ud83d\\ude40","\\ud83d\\udc7a","\\ud83d\\ude48","\\ud83d\\ude49","\\ud83d\\ude4a","\\ud83d\\udc80","\\ud83d\\udc7d","\\ud83d\\udca9","\\ud83d\\udd25","\\ud83d\\udca5","\\ud83d\\udca4","\\ud83d\\udc42","\\ud83d\\udc40","\\ud83d\\udc43","\\ud83d\\udc45","\\ud83d\\udc44","\\ud83d\\udc4d","\\ud83d\\udc4e","\\ud83d\\udc4c","\\ud83d\\udc4a","\\u270c","\\u270b","\\ud83d\\udc50","\\ud83d\\udc46","\\ud83d\\udc47","\\ud83d\\udc49","\\ud83d\\udc48","\\ud83d\\ude4f","\\ud83d\\udc4f","\\ud83d\\udcaa","\\ud83d\\udeb6","\\ud83c\\udfc3","\\ud83d\\udc83","\\ud83d\\udc6b","\\ud83d\\udc6a","\\ud83d\\udc6c","\\ud83d\\udc6d","\\ud83d\\udc85","\\ud83c\\udfa9","\\ud83d\\udc51","\\ud83d\\udc52","\\ud83d\\udc5f","\\ud83d\\udc5e","\\ud83d\\udc60","\\ud83d\\udc55","\\ud83d\\udc57","\\ud83d\\udc56","\\ud83d\\udc59","\\ud83d\\udc5c","\\ud83d\\udc53","\\ud83c\\udf80","\\ud83d\\udc84","\\ud83d\\udc9b","\\ud83d\\udc99","\\ud83d\\udc9c","\\ud83d\\udc9a","\\ud83d\\udc8d","\\ud83d\\udc8e","\\ud83d\\udc36","\\ud83d\\udc3a","\\ud83d\\udc31","\\ud83d\\udc2d","\\ud83d\\udc39","\\ud83d\\udc30","\\ud83d\\udc38","\\ud83d\\udc2f","\\ud83d\\udc28","\\ud83d\\udc3b","\\ud83d\\udc37","\\ud83d\\udc2e","\\ud83d\\udc17","\\ud83d\\udc34","\\ud83d\\udc11","\\ud83d\\udc18","\\ud83d\\udc3c","\\ud83d\\udc27","\\ud83d\\udc25","\\ud83d\\udc14","\\ud83d\\udc0d","\\ud83d\\udc22","\\ud83d\\udc1b","\\ud83d\\udc1d","\\ud83d\\udc1c","\\ud83d\\udc1e","\\ud83d\\udc0c","\\ud83d\\udc19","\\ud83d\\udc1a","\\ud83d\\udc1f","\\ud83d\\udc2c","\\ud83d\\udc0b","\\ud83d\\udc10","\\ud83d\\udc0a","\\ud83d\\udc2b","\\ud83c\\udf40","\\ud83c\\udf39","\\ud83c\\udf3b","\\ud83c\\udf41","\\ud83c\\udf3e","\\ud83c\\udf44","\\ud83c\\udf35","\\ud83c\\udf34","\\ud83c\\udf33","\\ud83c\\udf1e","\\ud83c\\udf1a","\\ud83c\\udf19","\\ud83c\\udf0e","\\ud83c\\udf0b","\\u26a1","\\u2614","\\u2744","\\u26c4","\\ud83c\\udf00","\\ud83c\\udf08","\\ud83c\\udf0a","\\ud83c\\udf93","\\ud83c\\udf86","\\ud83c\\udf83","\\ud83d\\udc7b","\\ud83c\\udf85","\\ud83c\\udf84","\\ud83c\\udf81","\\ud83c\\udf88","\\ud83d\\udd2e","\\ud83c\\udfa5","\\ud83d\\udcf7","\\ud83d\\udcbf","\\ud83d\\udcbb","\\u260e","\\ud83d\\udce1","\\ud83d\\udcfa","\\ud83d\\udcfb","\\ud83d\\udd09","\\ud83d\\udd14","\\u23f3","\\u23f0","\\u231a","\\ud83d\\udd12","\\ud83d\\udd11","\\ud83d\\udd0e","\\ud83d\\udca1","\\ud83d\\udd26","\\ud83d\\udd0c","\\ud83d\\udd0b","\\ud83d\\udebf","\\ud83d\\udebd","\\ud83d\\udd27","\\ud83d\\udd28","\\ud83d\\udeaa","\\ud83d\\udeac","\\ud83d\\udca3","\\ud83d\\udd2b","\\ud83d\\udd2a","\\ud83d\\udc8a","\\ud83d\\udc89","\\ud83d\\udcb0","\\ud83d\\udcb5","\\ud83d\\udcb3","\\u2709","\\ud83d\\udceb","\\ud83d\\udce6","\\ud83d\\udcc5","\\ud83d\\udcc1","\\u2702","\\ud83d\\udccc","\\ud83d\\udcce","\\u2712","\\u270f","\\ud83d\\udcd0","\\ud83d\\udcda","\\ud83d\\udd2c","\\ud83d\\udd2d","\\ud83c\\udfa8","\\ud83c\\udfac","\\ud83c\\udfa4","\\ud83c\\udfa7","\\ud83c\\udfb5","\\ud83c\\udfb9","\\ud83c\\udfbb","\\ud83c\\udfba","\\ud83c\\udfb8","\\ud83d\\udc7e","\\ud83c\\udfae","\\ud83c\\udccf","\\ud83c\\udfb2","\\ud83c\\udfaf","\\ud83c\\udfc8","\\ud83c\\udfc0","\\u26bd","\\u26be","\\ud83c\\udfbe","\\ud83c\\udfb1","\\ud83c\\udfc9","\\ud83c\\udfb3","\\ud83c\\udfc1","\\ud83c\\udfc7","\\ud83c\\udfc6","\\ud83c\\udfca","\\ud83c\\udfc4","\\u2615","\\ud83c\\udf7c","\\ud83c\\udf7a","\\ud83c\\udf77","\\ud83c\\udf74","\\ud83c\\udf55","\\ud83c\\udf54","\\ud83c\\udf5f","\\ud83c\\udf57","\\ud83c\\udf71","\\ud83c\\udf5a","\\ud83c\\udf5c","\\ud83c\\udf61","\\ud83c\\udf73","\\ud83c\\udf5e","\\ud83c\\udf69","\\ud83c\\udf66","\\ud83c\\udf82","\\ud83c\\udf70","\\ud83c\\udf6a","\\ud83c\\udf6b","\\ud83c\\udf6d","\\ud83c\\udf6f","\\ud83c\\udf4e","\\ud83c\\udf4f","\\ud83c\\udf4a","\\ud83c\\udf4b","\\ud83c\\udf52","\\ud83c\\udf47","\\ud83c\\udf49","\\ud83c\\udf53","\\ud83c\\udf51","\\ud83c\\udf4c","\\ud83c\\udf50","\\ud83c\\udf4d","\\ud83c\\udf46","\\ud83c\\udf45","\\ud83c\\udf3d","\\ud83c\\udfe1","\\ud83c\\udfe5","\\ud83c\\udfe6","\\u26ea","\\ud83c\\udff0","\\u26fa","\\ud83c\\udfed","\\ud83d\\uddfb","\\ud83d\\uddfd","\\ud83c\\udfa0","\\ud83c\\udfa1","\\u26f2","\\ud83c\\udfa2","\\ud83d\\udea2","\\ud83d\\udea4","\\u2693","\\ud83d\\ude80","\\u2708","\\ud83d\\ude81","\\ud83d\\ude82","\\ud83d\\ude8b","\\ud83d\\ude8e","\\ud83d\\ude8c","\\ud83d\\ude99","\\ud83d\\ude97","\\ud83d\\ude95","\\ud83d\\ude9b","\\ud83d\\udea8","\\ud83d\\ude94","\\ud83d\\ude92","\\ud83d\\ude91","\\ud83d\\udeb2","\\ud83d\\udea0","\\ud83d\\ude9c","\\ud83d\\udea6","\\u26a0","\\ud83d\\udea7","\\u26fd","\\ud83c\\udfb0","\\ud83d\\uddff","\\ud83c\\udfaa","\\ud83c\\udfad","\\ud83c\\uddef\\ud83c\\uddf5","\\ud83c\\uddf0\\ud83c\\uddf7","\\ud83c\\udde9\\ud83c\\uddea","\\ud83c\\udde8\\ud83c\\uddf3","\\ud83c\\uddfa\\ud83c\\uddf8","\\ud83c\\uddeb\\ud83c\\uddf7","\\ud83c\\uddea\\ud83c\\uddf8","\\ud83c\\uddee\\ud83c\\uddf9","\\ud83c\\uddf7\\ud83c\\uddfa","\\ud83c\\uddec\\ud83c\\udde7","1\\u20e3","2\\u20e3","3\\u20e3","4\\u20e3","5\\u20e3","6\\u20e3","7\\u20e3","8\\u20e3","9\\u20e3","0\\u20e3","\\ud83d\\udd1f","\\u2757","\\u2753","\\u2665","\\u2666","\\ud83d\\udcaf","\\ud83d\\udd17","\\ud83d\\udd31","\\ud83d\\udd34","\\ud83d\\udd35","\\ud83d\\udd36","\\ud83d\\udd37"]';
    const TD_PARAMS_CONVERSION = [
        'updateNewMessage' => [
            '_'                    => 'updateNewMessage',
            'disable_notification' => ['message', 'silent'],
            'message'              => ['message'],
         ],
         'message' => [
              '_'                  => 'message',
             'id'                  => ['id'],
             'sender_user_id'      => ['from_id'],
             'chat_id'             => ['to_id', 'choose_chat_id_from_botapi'],
             'send_state'          => ['choose_incoming_or_sent'],
             'can_be_edited'       => ['choose_can_edit'],
             'can_be_deleted'      => ['choose_can_delete'],
             'is_post'             => ['post'],
             'date'                => ['date'],
             'edit_date'           => ['edit_date'],
             'forward_info'        => ['fwd_info', 'choose_forward_info'],
             'reply_to_message_id' => ['reply_to_msg_id'],
             'ttl'                 => ['choose_ttl'],
             'ttl_expires_in'      => ['choose_ttl_expires_in'],
             'via_bot_user_id'     => ['via_bot_id'],
             'views'               => ['views'],
             'content'             => ['choose_message_content'],
             'reply_markup'        => ['reply_markup'],
         ],

         'messages.sendMessage' => [
             'chat_id'               => ['peer'],
             'reply_to_message_id'   => ['reply_to_msg_id'],
             'disable_notification'  => ['silent'],
             'from_background'       => ['background'],
             'input_message_content' => ['choose_message_content'],
             'reply_markup'          => ['reply_markup'],
         ],

    ];
    const TD_REVERSE = [
        'sendMessage'=> 'messages.sendMessage',
    ];
    const TD_IGNORE = ['updateMessageID'];

    public $hook_url = false;
    public $settings = [];
    private $config = ['expires' => -1];
    public $authorization = null;
    public $authorized = 0;

    private $rsa_keys = [];
    private $last_recv = 0;
    private $dh_config = ['version' => 0];
    public $chats = [];
    public $last_stored = 0;
    public $qres = [];

    public $full_chats = [];
    private $msg_ids = [];
    private $v = 0;

    private $dialog_params = ['_' => 'MadelineProto.dialogParams', 'limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' =>  ['_' => 'inputPeerEmpty'], 'count' => 0];
    private $zero;
    private $one;
    private $two;
    private $three;
    private $four;
    private $twoe1984;
    private $twoe2047;
    private $twoe2048;

    private $ipv6 = false;
    public $run_workers = false;
    public $threads = false;
    public $setdem = false;
    public $storage = [];
    private $emojis;

    public function ___construct($settings = [])
    {
        if (!defined('\phpseclib\Crypt\AES::MODE_IGE')) {
            throw new Exception('Please install this fork of phpseclib: https://github.com/danog/phpseclib');
        }
        $this->emojis = json_decode(self::JSON_EMOJIS);
        \danog\MadelineProto\Logger::class_exists();

        // Detect ipv6
        $this->ipv6 = (bool) strlen(@file_get_contents('http://ipv6.test-ipv6.com/', false, stream_context_create(['http' => ['timeout' => 1]]))) > 0;

        // Parse settings
        $this->parse_settings($settings);

        // Connect to servers
        \danog\MadelineProto\Logger::log(['Istantiating DataCenter...'], Logger::ULTRA_VERBOSE);
        if (isset($this->datacenter)) {
            $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        } else {
            $this->datacenter = new DataCenter($this->settings['connection'], $this->settings['connection_settings']);
        }
        // Load rsa keys
        \danog\MadelineProto\Logger::log(['Loading RSA keys...'], Logger::ULTRA_VERBOSE);
        foreach ($this->settings['authorization']['rsa_keys'] as $key) {
            $key = new RSA($key);
            $this->rsa_keys[$key->fp] = $key;
        }

        // Istantiate TL class
        \danog\MadelineProto\Logger::log(['Translating tl schemas...'], Logger::ULTRA_VERBOSE);
        $this->construct_TL($this->settings['tl_schema']['src']);
        /*
         * ***********************************************************************
         * Define some needed numbers for BigInteger
         */
        \danog\MadelineProto\Logger::log(['Executing dh_prime checks (0/3)...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        $this->zero = new \phpseclib\Math\BigInteger(0);
        $this->one = new \phpseclib\Math\BigInteger(1);
        $this->two = new \phpseclib\Math\BigInteger(2);
        $this->three = new \phpseclib\Math\BigInteger(3);
        $this->four = new \phpseclib\Math\BigInteger(4);
        $this->twoe1984 = new \phpseclib\Math\BigInteger('1751908409537131537220509645351687597690304110853111572994449976845956819751541616602568796259317428464425605223064365804210081422215355425149431390635151955247955156636234741221447435733643262808668929902091770092492911737768377135426590363166295684370498604708288556044687341394398676292971255828404734517580702346564613427770683056761383955397564338690628093211465848244049196353703022640400205739093118270803778352768276670202698397214556629204420309965547056893233608758387329699097930255380715679250799950923553703740673620901978370802540218870279314810722790539899334271514365444369275682816');
        $this->twoe2047 = new \phpseclib\Math\BigInteger('16158503035655503650357438344334975980222051334857742016065172713762327569433945446598600705761456731844358980460949009747059779575245460547544076193224141560315438683650498045875098875194826053398028819192033784138396109321309878080919047169238085235290822926018152521443787945770532904303776199561965192760957166694834171210342487393282284747428088017663161029038902829665513096354230157075129296432088558362971801859230928678799175576150822952201848806616643615613562842355410104862578550863465661734839271290328348967522998634176499319107762583194718667771801067716614802322659239302476074096777926805529798115328');
        $this->twoe2048 = new \phpseclib\Math\BigInteger('32317006071311007300714876688669951960444102669715484032130345427524655138867890893197201411522913463688717960921898019494119559150490921095088152386448283120630877367300996091750197750389652106796057638384067568276792218642619756161838094338476170470581645852036305042887575891541065808607552399123930385521914333389668342420684974786564569494856176035326322058077805659331026192708460314150258592864177116725943603718461857357598351152301645904403697613233287231227125684710820209725157101726931323469678542580656697935045997268352998638215525166389437335543602135433229604645318478604952148193555853611059596230656');

        $this->setup_threads();

        $this->connect_to_all_dcs();
        $this->datacenter->curdc = 2;

        if (!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) {
            try {
                $nearest_dc = $this->method_call('help.getNearestDc', [], ['datacenter' => $this->datacenter->curdc]);
                \danog\MadelineProto\Logger::log(["We're in ".$nearest_dc['country'].', current dc is '.$nearest_dc['this_dc'].', nearest dc is '.$nearest_dc['nearest_dc'].'.'], Logger::NOTICE);

                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->datacenter->curdc = (int) $nearest_dc['nearest_dc'];
                    $this->settings['connection_settings']['default_dc'] = (int) $nearest_dc['nearest_dc'];
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        $this->get_config([], ['datacenter' => $this->datacenter->curdc]);
        $this->v = self::V;

        return $this->settings;
    }

    public function __sleep()
    {
        return ['encrypted_layer', 'settings', 'config', 'authorization', 'authorized', 'rsa_keys', 'last_recv', 'dh_config', 'chats', 'last_stored', 'qres', 'pending_updates', 'updates_state', 'got_state', 'channels_state', 'updates', 'updates_key', 'full_chats', 'msg_ids', 'dialog_params', 'datacenter', 'v', 'constructors', 'td_constructors', 'methods', 'td_methods', 'td_descriptions', 'twoe1984', 'twoe2047', 'twoe2048', 'zero', 'one', 'two', 'three', 'four', 'temp_requested_secret_chats', 'temp_rekeyed_secret_chats', 'secret_chats', 'hook_url', 'storage', 'emojis'];
    }

    public function __wakeup()
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->setup_logger();
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        if (!defined('\phpseclib\Crypt\AES::MODE_IGE')) {
            throw new Exception('Please install this fork of phpseclib: https://github.com/danog/phpseclib');
        }
        foreach ($this->calls as $id => $controller) {
            if (!is_object($controller)) {
                unset($this->calls[$id]);
            } elseif ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->setMadeline($this);
                $controller->discard();
            } else {
                $controller->setMadeline($this);
            }
        }
        // Detect ipv6
        $this->ipv6 = (bool) strlen(@file_get_contents('http://ipv6.test-ipv6.com/', false, stream_context_create(['http' => ['timeout' => 1]]))) > 0;
        preg_match('/const V = (\d+);/', file_get_contents('https://raw.githubusercontent.com/danog/MadelineProto/master/src/danog/MadelineProto/MTProto.php'), $matches);
        $keys = array_keys((array) get_object_vars($this));
        if (count($keys) !== count(array_unique($keys))) {
            throw new Bug74586Exception();
        }

        /*
        if (method_exists($this->datacenter, 'wakeup')) $this->datacenter = $this->datacenter->wakeup();
        foreach ($this->rsa_keys as $key => $elem) {
            if (method_exists($elem, 'wakeup')) $this->rsa_keys[$key] = $elem->wakeup();
        }
        foreach ($this->datacenter->sockets as $key => $elem) {
            if (method_exists($elem, 'wakeup')) $this->datacenter->sockets[$key] = $elem->wakeup();
        }
        */
        if (isset($this->data)) {
            foreach ($this->data as $k => $v) {
                $this->{$k} = $v;
            }
            unset($this->data);
        }
        if ($this->authorized === true) {
            $this->authorized = self::LOGGED_IN;
        }
        $this->updates_state['sync_loading'] = false;
        foreach ($this->channels_state as $key => $state) {
            $this->channels_state[$key]['sync_loading'] = false;
        }
        foreach (debug_backtrace(0) as $trace) {
            if (isset($trace['function']) && isset($trace['class']) && $trace['function'] === 'deserialize' && $trace['class'] === 'danog\MadelineProto\Serialization') {
                $this->updates_state['sync_loading'] = isset($trace['args'][1]) && $trace['args'][1];
            }
        }
        $force = false;
        $this->reset_session();
        if (!isset($this->v) || $this->v !== self::V) {
            \danog\MadelineProto\Logger::log(['Serialization is out of date, reconstructing object!'], Logger::WARNING);
            $settings = $this->settings;
            if (isset($settings['updates']['callback'][0]) && $settings['updates']['callback'][0] === $this) {
                $settings['updates']['callback'] = 'get_updates_update_handler';
            }
            unset($settings['tl_schema']);
            if (isset($settings['authorization']['rsa_key'])) {
                unset($settings['authorization']['rsa_key']);
            }
            foreach ($this->full_chats as $id => $full) {
                $this->full_chats[$id] = ['full' => $full['full'], 'last_update' => $full['last_update']];
            }
            foreach ($settings['connection_settings'] as $key => &$connection) {
                if (!is_array($connection)) {
                    continue;
                }
                if (!isset($connection['proxy'])) {
                    $connection['proxy'] = '\Socket';
                }
                if (!isset($connection['proxy_extra'])) {
                    $connection['proxy_extra'] = [];
                }
            }
            if (!isset($settings['authorization']['rsa_key'])) {
                unset($settings['authorization']['rsa_key']);
            }
            $this->reset_session(true, true);
            $this->config = ['expires' => -1];
            $this->__construct($settings);
            $force = true;
        }
        $this->setup_threads();
        $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        if ($this->authorized === self::LOGGED_IN) {
            $this->get_self();
            $this->get_cdn_config($this->datacenter->curdc);
        }
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot']) {
            $this->get_dialogs($force);
        }
        if ($this->authorized === self::LOGGED_IN && $this->settings['updates']['handle_updates'] && !$this->updates_state['sync_loading']) {
            \danog\MadelineProto\Logger::log(['Getting updates after deserialization...'], Logger::NOTICE);
            $this->get_updates_difference();
        }
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        if (isset(Logger::$storage[spl_object_hash($this)])) {
            $this->run_workers = false;
            while ($number = Logger::$storage[spl_object_hash($this)]->collect()) {
                \danog\MadelineProto\Logger::log(['Shutting down reader pool, '.$number.' jobs left'], \danog\MadelineProto\Logger::NOTICE);
            }
            Logger::$storage[spl_object_hash($this)]->shutdown();
        }
    }

    public function setup_threads()
    {
        if ($this->threads = $this->run_workers = class_exists('\Pool') && in_array(php_sapi_name(), ['cli', 'phpdbg']) && $this->settings['threading']['allow_threading'] && extension_loaded('pthreads')) {
            \danog\MadelineProto\Logger::log(['THREADING IS ENABLED'], \danog\MadelineProto\Logger::NOTICE);
            $this->start_threads();
        }
    }

    public function start_threads()
    {
        if ($this->threads && !is_object(\Thread::getCurrentThread())) {
            $dcs = $this->datacenter->get_dcs(false);
            if (!isset(Logger::$storage[spl_object_hash($this)])) {
                Logger::$storage[spl_object_hash($this)] = new \Pool(count($dcs));
            }
            if (!isset($this->readers)) {
                $this->readers = [];
            }
            foreach ($dcs as $dc) {
                if (!isset($this->readers[$dc])) {
                    Logger::log(['Socket reader on DC '.$dc.': CREATING'], Logger::WARNING);
                    $this->readers[$dc] = new \danog\MadelineProto\Threads\SocketReader($this, $dc);
                }
                if (!$this->readers[$dc]->isRunning()) {
                    Logger::log(['Socket reader on DC '.$dc.': SUBMITTING'], Logger::WARNING);
                    $this->readers[$dc]->garbage = false;
                    Logger::$storage[spl_object_hash($this)]->submit($this->readers[$dc]);
                    Logger::log(['Socket reader on DC '.$dc.': WAITING'], Logger::WARNING);
                    while (!$this->readers[$dc]->ready);
                    Logger::log(['Socket reader on DC '.$dc.': READY'], Logger::WARNING);
                } else {
                    Logger::log(['Socket reader on DC '.$dc.': WORKING'], Logger::ULTRA_VERBOSE);
                }
            }
        }

        return true;
    }

    public function parse_settings($settings)
    {
        // Detect device model
        try {
            $device_model = php_uname('s');
        } catch (\danog\MadelineProto\Exception $e) {
            $device_model = 'Web server';
        }

        // Detect system version
        try {
            $system_version = php_uname('r');
        } catch (\danog\MadelineProto\Exception $e) {
            $system_version = phpversion();
        }

        // Set default settings
        $default_settings = [
            'authorization' => [ // Authorization settings
                'default_temp_auth_key_expires_in' => 31557600, // validity of temporary keys and the binding of the temporary and permanent keys
                'rsa_keys'                         => [
                    "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----",
                    "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAxq7aeLAqJR20tkQQMfRn+ocfrtMlJsQ2Uksfs7Xcoo77jAid0bRt\nksiVmT2HEIJUlRxfABoPBV8wY9zRTUMaMA654pUX41mhyVN+XoerGxFvrs9dF1Ru\nvCHbI02dM2ppPvyytvvMoefRoL5BTcpAihFgm5xCaakgsJ/tH5oVl74CdhQw8J5L\nxI/K++KJBUyZ26Uba1632cOiq05JBUW0Z2vWIOk4BLysk7+U9z+SxynKiZR3/xdi\nXvFKk01R3BHV+GUKM2RYazpS/P8v7eyKhAbKxOdRcFpHLlVwfjyM1VlDQrEZxsMp\nNTLYXb6Sce1Uov0YtNx5wEowlREH1WOTlwIDAQAB\n-----END RSA PUBLIC KEY-----",
                    "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAsQZnSWVZNfClk29RcDTJQ76n8zZaiTGuUsi8sUhW8AS4PSbPKDm+\nDyJgdHDWdIF3HBzl7DHeFrILuqTs0vfS7Pa2NW8nUBwiaYQmPtwEa4n7bTmBVGsB\n1700/tz8wQWOLUlL2nMv+BPlDhxq4kmJCyJfgrIrHlX8sGPcPA4Y6Rwo0MSqYn3s\ng1Pu5gOKlaT9HKmE6wn5Sut6IiBjWozrRQ6n5h2RXNtO7O2qCDqjgB2vBxhV7B+z\nhRbLbCmW0tYMDsvPpX5M8fsO05svN+lKtCAuz1leFns8piZpptpSCFn7bWxiA9/f\nx5x17D7pfah3Sy2pA+NDXyzSlGcKdaUmwQIDAQAB\n-----END RSA PUBLIC KEY-----",
                    "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwqjFW0pi4reKGbkc9pK83Eunwj/k0G8ZTioMMPbZmW99GivMibwa\nxDM9RDWabEMyUtGoQC2ZcDeLWRK3W8jMP6dnEKAlvLkDLfC4fXYHzFO5KHEqF06i\nqAqBdmI1iBGdQv/OQCBcbXIWCGDY2AsiqLhlGQfPOI7/vvKc188rTriocgUtoTUc\n/n/sIUzkgwTqRyvWYynWARWzQg0I9olLBBC2q5RQJJlnYXZwyTL3y9tdb7zOHkks\nWV9IMQmZmyZh/N7sMbGWQpt4NMchGpPGeJ2e5gHBjDnlIf2p1yZOYeUYrdbwcS0t\nUiggS4UeE8TzIuXFQxw7fzEIlmhIaq3FnwIDAQAB\n-----END RSA PUBLIC KEY-----",
                ], // RSA public keys
            ],
            'connection' => [ // List of datacenters/subdomains where to connect
                'ssl_subdomains' => [ // Subdomains of web.telegram.org for https protocol
                    1 => 'pluto',
                    2 => 'venus',
                    3 => 'aurora',
                    4 => 'vesta',
                    5 => 'flora', // musa oh wait no :(
                ],
                'test' => [ // Test datacenters
                    'ipv4' => [ // ipv4 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '149.154.167.40',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                        ],
                     ],
                    'ipv6' => [ // ipv6 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                ],
                'main' => [ // Main datacenters
                    'ipv4' => [ // ipv4 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '149.154.167.51',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                    'ipv6' => [ // ipv6 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                ],
            ],
            'connection_settings' => [ // connection settings
                'all' => [ // These settings will be applied on every datacenter that hasn't a custom settings subarray...
                    'protocol'     => 'tcp_full', // can be tcp_full, tcp_abridged, tcp_intermediate, http, https, obfuscated2, udp (unsupported)
                    'test_mode'    => false, // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                    'ipv6'         => $this->ipv6, // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                    'timeout'      => 2, // timeout for sockets
                    'proxy'        => '\Socket', // The proxy class to use
                    'proxy_extra'  => [], // Extra parameters to pass to the proxy class using setExtra
                ],
            ],
            'app_info' => [ // obtained in https://my.telegram.org
                //'api_id'          => 6,
                //'api_hash'        => 'eb06d4abfb49dc3eeb1aeb98ae0f581e',
                'device_model'    => $device_model,
                'system_version'  => $system_version,
                'app_version'     => 'Unicorn', // ðŸŒš
//                'app_version'     => self::V,
                'lang_code'       => 'en',
            ],
            'tl_schema'     => [ // TL scheme files
                'layer'         => 71, // layer version
                'src'           => [
                    'mtproto'      => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
                    'telegram'     => __DIR__.'/TL_telegram_v71.tl', // telegram TL scheme
                    'secret'       => __DIR__.'/TL_secret.tl', // secret chats TL scheme
                    'calls'        => __DIR__.'/TL_calls.tl', // calls TL scheme
                    //'td'           => __DIR__.'/TL_td.tl', // telegram-cli TL scheme
                    'botAPI'       => __DIR__.'/TL_botAPI.tl', // bot API TL scheme for file ids
                ],
            ],
            'logger'       => [ // Logger settings
                /*
                 * logger modes:
                 * 0 - No logger
                 * 1 - Log to the default logger destination
                 * 2 - Log to file defined in second parameter
                 * 3 - Echo logs
                 * 4 - Call callable provided in logger_param. logger_param must accept two parameters: array $message, int $level
                 *     $message is an array containing the messages the log, $level, is the logging level
                 */
                'logger'             => 1, // write to
                'logger_param'       => '/tmp/MadelineProto.log',
                'logger'             => 3, // overwrite previous setting and echo logs
                'logger_level'       => Logger::VERBOSE, // Logging level, available logging levels are: ULTRA_VERBOSE, VERBOSE, NOTICE, WARNING, ERROR, FATAL_ERROR. Can be provided as last parameter to the logging function.
                'rollbar_token'      => 'c07d9b2f73c2461297b0beaef6c1662f',
                //'rollbar_token'      => 'f9fff6689aea4905b58eec73f66c791d' // You can provide a token for the rollbar log management system
            ],
            'max_tries'         => [
                'query'         => 5, // How many times should I try to call a method or send an object before throwing an exception
                'authorization' => 5, // How many times should I try to generate an authorization key before throwing an exception
                'response'      => 5, // How many times should I try to get a response of a query before throwing an exception
            ],
            'flood_timeout'     => [
                'wait_if_lt'    => 20, // Sleeps if flood block time is lower than this
            ],
            'msg_array_limit'        => [ // How big should be the arrays containing the incoming and outgoing messages?
                'incoming'   => 200,
                'outgoing'   => 200,
                'call_queue' => 200,
            ],
            'peer'      => [
                'full_info_cache_time' => 60,
            ],
            'updates'   => [
                'handle_updates'      => true, // Should I handle updates?
                'callback'            => 'get_updates_update_handler', // A callable function that will be called every time an update is received, must accept an array (for the update) as the only parameter
            ],
            'secret_chats' => [
                'accept_chats'      => true, // Should I accept secret chats? Can be true, false or on array of user ids from which to accept chats
            ],
            'threading' => [
                'allow_threading' => false, // Should I use threading, if it is enabled?
                'handler_workers' => 10, // How many workers should every message handler pool of each socket reader have
            ],
            'pwr' => [
                'pwr'      => false,      // Need info ?
                'db_token' => false, // Need info ?
                'strict'   => false,   // Need info ?
                'requests' => true,  // Should I get info about unknown peers from PWRTelegram?
            ],
        ];
        $settings = array_replace_recursive($this->array_cast_recursive($default_settings, true), $this->array_cast_recursive($settings, true));
        if (!isset($settings['app_info']['api_id'])) {
            throw new \danog\MadelineProto\Exception('You must provide an api key and an api id, get your own @ my.telegram.org', 0, null, 'MadelineProto', 1);
        }

        if ($settings['app_info']['api_id'] < 20) {
            $settings['connection_settings']['all']['protocol'] = 'obfuscated2';
        }
        switch ($settings['logger']['logger_level']) {
            case 'ULTRA_VERBOSE': $settings['logger']['logger_level'] = 5; break;
            case 'VERBOSE': $settings['logger']['logger_level'] = 4; break;
            case 'NOTICE': $settings['logger']['logger_level'] = 3; break;
            case 'WARNING': $settings['logger']['logger_level'] = 2; break;
            case 'ERROR': $settings['logger']['logger_level'] = 1; break;
            case 'FATAL ERROR': $settings['logger']['logger_level'] = 0; break;
        }

        $this->settings = $settings;
        // Setup logger
        $this->setup_logger();
    }

    public function setup_logger()
    {
        \Rollbar\Rollbar::init(['environment' => 'production', 'root' => __DIR__, 'access_token' => (isset($this->settings['logger']['rollbar_token']) && !in_array($this->settings['logger']['rollbar_token'], ['f9fff6689aea4905b58eec73f66c791d', '300afd7ccef346ea84d0c185ae831718', '11a8c2fe4c474328b40a28193f8d63f5', 'beef2d426496462ba34dcaad33d44a14'])) || $this->settings['pwr']['pwr'] ? $this->settings['logger']['rollbar_token'] : 'c07d9b2f73c2461297b0beaef6c1662f'], false, false);
        \danog\MadelineProto\Logger::constructor($this->settings['logger']['logger'], $this->settings['logger']['logger_param'], isset($this->authorization['user']) ? (isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id']) : '', isset($this->settings['logger']['logger_level']) ? $this->settings['logger']['logger_level'] : Logger::VERBOSE);
    }

    public function reset_session($de = true, $auth_key = false)
    {
        if (!is_object($this->datacenter)) {
            throw new Exception('The session is corrupted!');
        }
        foreach ($this->datacenter->sockets as $id => $socket) {
            if ($de) {
                \danog\MadelineProto\Logger::log(['Resetting session id'.($auth_key ? ', authorization key' : '').' and seq_no in DC '.$id.'...'], Logger::VERBOSE);
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
            }
            if ($auth_key) {
                $socket->temp_auth_key = null;
            }
            $socket->incoming_messages = [];
            $socket->outgoing_messages = [];
            $socket->new_outgoing = [];
            $socket->new_incoming = [];
        }
    }

    // Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info
    public function connect_to_all_dcs()
    {
        foreach ($old = $this->datacenter->get_dcs() as $new_dc) {
            $this->datacenter->dc_connect($new_dc);
        }
        $this->setup_threads();
        $this->init_authorization();
        if ($old !== $this->datacenter->get_dcs()) {
            $this->connect_to_all_dcs();
        }
    }

    private $initing_authorization = false;

    // Creates authorization keys
    public function init_authorization()
    {
        $this->initing_authorization = true;
        $this->updates_state['sync_loading'] = true;
        foreach ($this->datacenter->sockets as $id => $socket) {
            if (strpos($id, 'media')) {
                continue;
            }
            $cdn = strpos($id, 'cdn');
            if ($socket->session_id === null) {
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
            }
            if ($socket->temp_auth_key === null || $socket->auth_key === null) {
                if ($socket->auth_key === null && !$cdn) {
                    \danog\MadelineProto\Logger::log(['Generating permanent authorization key for DC '.$id.'...'], Logger::NOTICE);
                    $socket->auth_key = $this->create_auth_key(-1, $id);
                }
                \danog\MadelineProto\Logger::log(['Generating temporary authorization key for DC '.$id.'...'], Logger::NOTICE);
                $socket->temp_auth_key = $this->create_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                if (!$cdn) {
                    $this->bind_temp_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                    $this->get_config($this->write_client_info('help.getConfig', [], ['datacenter' => $id]));
                }
                if (in_array($socket->protocol, ['http', 'https'])) {
                    $this->method_call('http_wait', ['max_wait' => 0, 'wait_after' => 0, 'max_delay' => 0], ['datacenter' => $id]);
                }
            }
        }
        $this->initing_authorization = false;
        $this->updates_state['sync_loading'] = false;
    }

    public function sync_authorization($authorized_dc)
    {
        $this->updates_state['sync_loading'] = true;
        foreach ($this->datacenter->sockets as $new_dc => $socket) {
            if (($int_dc = preg_replace('|/D+|', '', $new_dc)) == $authorized_dc) {
                continue;
            }
            if ($int_dc != $new_dc) {
                continue;
            }
            \danog\MadelineProto\Logger::log([$int_dc, $new_dc]);
            if (strpos($new_dc, '_') !== false) {
                continue;
            }
            \danog\MadelineProto\Logger::log(['Copying authorization from dc '.$authorized_dc.' to dc '.$new_dc.'...'], Logger::VERBOSE);
            $exported_authorization = $this->method_call('auth.exportAuthorization', ['dc_id' => $new_dc], ['datacenter' => $authorized_dc]);
            $this->method_call('auth.logOut', [], ['datacenter' => $new_dc]);
            $authorization = $this->method_call('auth.importAuthorization', $exported_authorization, ['datacenter' => $new_dc]);
        }
        $this->updates_state['sync_loading'] = false;

        return $authorization;
    }

    public function write_client_info($method, $arguments = [], $options = [])
    {
        \danog\MadelineProto\Logger::log(['Writing client info (also executing '.$method.')...'], Logger::NOTICE);

        return $this->method_call(
            'invokeWithLayer',
            [
                'layer' => $this->settings['tl_schema']['layer'],
                'query' => $this->serialize_method('initConnection',
                    [
                        'api_id'                => $this->settings['app_info']['api_id'],
                        'api_hash'              => $this->settings['app_info']['api_hash'],
                        'device_model'          => strpos($options['datacenter'], 'cdn') === false ? $this->settings['app_info']['device_model'] : 'n/a',
                        'system_version'        => strpos($options['datacenter'], 'cdn') === false ? $this->settings['app_info']['system_version'] : 'n/a',
                        'app_version'           => $this->settings['app_info']['app_version'],
                        'system_lang_code'      => $this->settings['app_info']['lang_code'],
                        'lang_code'             => $this->settings['app_info']['lang_code'],
                        'lang_pack'             => '',
                        'query'                 => $this->serialize_method($method, $arguments),
                    ]
                ),
            ],
            $options
        );
    }

    public function get_config($config = [], $options = [])
    {
        if ($this->config['expires'] > time()) {
            return;
        }
        $this->config = empty($config) ? $this->method_call('help.getConfig', $config, $options) : $config;
        $this->parse_config();
    }

    public function get_cdn_config($datacenter)
    {
        /*
         * ***********************************************************************
         * Fetch RSA keys for CDN datacenters
         */
        try {
            foreach ($this->method_call('help.getCdnConfig', [], ['datacenter' => $datacenter])['public_keys'] as $curkey) {
                $tempkey = new \danog\MadelineProto\RSA($curkey['public_key']);
                $this->rsa_keys[$tempkey->fp] = $tempkey;
            }
        } catch (\danog\MadelineProto\TL\Exception $e) {
            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    public function parse_config()
    {
        if (isset($this->config['dc_options'])) {
            $this->parse_dc_options($this->config['dc_options']);
            unset($this->config['dc_options']);
        }
        \danog\MadelineProto\Logger::log(['Updated config!', $this->config], Logger::NOTICE);
    }

    public function parse_dc_options($dc_options)
    {
        foreach ($dc_options as $dc) {
            $test = $this->config['test_mode'] ? 'test' : 'main';
            $id = $dc['id'];
            if (isset($dc['cdn'])) {
                $id .= $dc['cdn'] ? '_cdn' : '';
            }
            $id .= $dc['media_only'] ? '_media' : '';
            $ipv6 = ($dc['ipv6'] ? 'ipv6' : 'ipv4');
            $id .= (isset($this->settings['connection'][$test][$ipv6][$id]) && $this->settings['connection'][$test][$ipv6][$id]['ip_address'] != $dc['ip_address']) ? '_bk' : '';
            if (is_numeric($id)) {
                $id = (int) $id;
            }
            unset($dc['cdn']);
            unset($dc['media_only']);
            unset($dc['id']);
            unset($dc['ipv6']);
            $this->settings['connection'][$test][$ipv6][$id] = $dc;
        }
        $curdc = $this->datacenter->curdc;
        $this->datacenter->dclist = $this->settings['connection'];
        $this->datacenter->settings = $this->settings['connection_settings'];
        $this->connect_to_all_dcs();
        $this->datacenter->curdc = $curdc;
    }

    public function get_self()
    {
        if ($this->authorization === null) {
            $this->authorization = ['user' => $this->method_call('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], ['datacenter' => $this->datacenter->curdc])[0]];
        }

        return $this->authorization['user'];
    }

    const ALL_MIMES = [
      'png' => [
        0 => 'image/png',
        1 => 'image/x-png',
      ],
      'bmp' => [
        0  => 'image/bmp',
        1  => 'image/x-bmp',
        2  => 'image/x-bitmap',
        3  => 'image/x-xbitmap',
        4  => 'image/x-win-bitmap',
        5  => 'image/x-windows-bmp',
        6  => 'image/ms-bmp',
        7  => 'image/x-ms-bmp',
        8  => 'application/bmp',
        9  => 'application/x-bmp',
        10 => 'application/x-win-bitmap',
      ],
      'gif' => [
        0 => 'image/gif',
      ],
      'jpeg' => [
        0 => 'image/jpeg',
        1 => 'image/pjpeg',
      ],
      'xspf' => [
        0 => 'application/xspf+xml',
      ],
      'vlc' => [
        0 => 'application/videolan',
      ],
      'wmv' => [
        0 => 'video/x-ms-wmv',
        1 => 'video/x-ms-asf',
      ],
      'au' => [
        0 => 'audio/x-au',
      ],
      'ac3' => [
        0 => 'audio/ac3',
      ],
      'flac' => [
        0 => 'audio/x-flac',
      ],
      'ogg' => [
        0 => 'audio/ogg',
        1 => 'video/ogg',
        2 => 'application/ogg',
      ],
      'kmz' => [
        0 => 'application/vnd.google-earth.kmz',
      ],
      'kml' => [
        0 => 'application/vnd.google-earth.kml+xml',
      ],
      'rtx' => [
        0 => 'text/richtext',
      ],
      'rtf' => [
        0 => 'text/rtf',
      ],
      'jar' => [
        0 => 'application/java-archive',
        1 => 'application/x-java-application',
        2 => 'application/x-jar',
      ],
      'zip' => [
        0 => 'application/x-zip',
        1 => 'application/zip',
        2 => 'application/x-zip-compressed',
        3 => 'application/s-compressed',
        4 => 'multipart/x-zip',
      ],
      '7zip' => [
        0 => 'application/x-compressed',
      ],
      'xml' => [
        0 => 'application/xml',
        1 => 'text/xml',
      ],
      'svg' => [
        0 => 'image/svg+xml',
      ],
      '3g2' => [
        0 => 'video/3gpp2',
      ],
      '3gp' => [
        0 => 'video/3gp',
        1 => 'video/3gpp',
      ],
      'mp4' => [
        0 => 'video/mp4',
      ],
      'm4a' => [
        0 => 'audio/x-m4a',
      ],
      'f4v' => [
        0 => 'video/x-f4v',
      ],
      'flv' => [
        0 => 'video/x-flv',
      ],
      'webm' => [
        0 => 'video/webm',
      ],
      'aac' => [
        0 => 'audio/x-acc',
      ],
      'm4u' => [
        0 => 'application/vnd.mpegurl',
      ],
      'pdf' => [
        0 => 'application/pdf',
        1 => 'application/octet-stream',
      ],
      'pptx' => [
        0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      ],
      'ppt' => [
        0 => 'application/powerpoint',
        1 => 'application/vnd.ms-powerpoint',
        2 => 'application/vnd.ms-office',
        3 => 'application/msword',
      ],
      'docx' => [
        0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      ],
      'xlsx' => [
        0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        1 => 'application/vnd.ms-excel',
      ],
      'xl' => [
        0 => 'application/excel',
      ],
      'xls' => [
        0 => 'application/msexcel',
        1 => 'application/x-msexcel',
        2 => 'application/x-ms-excel',
        3 => 'application/x-excel',
        4 => 'application/x-dos_ms_excel',
        5 => 'application/xls',
        6 => 'application/x-xls',
      ],
      'xsl' => [
        0 => 'text/xsl',
      ],
      'mpeg' => [
        0 => 'video/mpeg',
      ],
      'mov' => [
        0 => 'video/quicktime',
      ],
      'avi' => [
        0 => 'video/x-msvideo',
        1 => 'video/msvideo',
        2 => 'video/avi',
        3 => 'application/x-troff-msvideo',
      ],
      'movie' => [
        0 => 'video/x-sgi-movie',
      ],
      'log' => [
        0 => 'text/x-log',
      ],
      'txt' => [
        0 => 'text/plain',
      ],
      'css' => [
        0 => 'text/css',
      ],
      'html' => [
        0 => 'text/html',
      ],
      'wav' => [
        0 => 'audio/x-wav',
        1 => 'audio/wave',
        2 => 'audio/wav',
      ],
      'xhtml' => [
        0 => 'application/xhtml+xml',
      ],
      'tar' => [
        0 => 'application/x-tar',
      ],
      'tgz' => [
        0 => 'application/x-gzip-compressed',
      ],
      'psd' => [
        0 => 'application/x-photoshop',
        1 => 'image/vnd.adobe.photoshop',
      ],
      'exe' => [
        0 => 'application/x-msdownload',
      ],
      'js' => [
        0 => 'application/x-javascript',
      ],
      'mp3' => [
        0 => 'audio/mpeg',
        1 => 'audio/mpg',
        2 => 'audio/mpeg3',
        3 => 'audio/mp3',
      ],
      'rar' => [
        0 => 'application/x-rar',
        1 => 'application/rar',
        2 => 'application/x-rar-compressed',
      ],
      'gzip' => [
        0 => 'application/x-gzip',
      ],
      'hqx' => [
        0 => 'application/mac-binhex40',
        1 => 'application/mac-binhex',
        2 => 'application/x-binhex40',
        3 => 'application/x-mac-binhex40',
      ],
      'cpt' => [
        0 => 'application/mac-compactpro',
      ],
      'bin' => [
        0 => 'application/macbinary',
        1 => 'application/mac-binary',
        2 => 'application/x-binary',
        3 => 'application/x-macbinary',
      ],
      'oda' => [
        0 => 'application/oda',
      ],
      'ai' => [
        0 => 'application/postscript',
      ],
      'smil' => [
        0 => 'application/smil',
      ],
      'mif' => [
        0 => 'application/vnd.mif',
      ],
      'wbxml' => [
        0 => 'application/wbxml',
      ],
      'wmlc' => [
        0 => 'application/wmlc',
      ],
      'dcr' => [
        0 => 'application/x-director',
      ],
      'dvi' => [
        0 => 'application/x-dvi',
      ],
      'gtar' => [
        0 => 'application/x-gtar',
      ],
      'php' => [
        0 => 'application/x-httpd-php',
        1 => 'application/php',
        2 => 'application/x-php',
        3 => 'text/php',
        4 => 'text/x-php',
        5 => 'application/x-httpd-php-source',
      ],
      'swf' => [
        0 => 'application/x-shockwave-flash',
      ],
      'sit' => [
        0 => 'application/x-stuffit',
      ],
      'z' => [
        0 => 'application/x-compress',
      ],
      'mid' => [
        0 => 'audio/midi',
      ],
      'aif' => [
        0 => 'audio/x-aiff',
        1 => 'audio/aiff',
      ],
      'ram' => [
        0 => 'audio/x-pn-realaudio',
      ],
      'rpm' => [
        0 => 'audio/x-pn-realaudio-plugin',
      ],
      'ra' => [
        0 => 'audio/x-realaudio',
      ],
      'rv' => [
        0 => 'video/vnd.rn-realvideo',
      ],
      'jp2' => [
        0 => 'image/jp2',
        1 => 'video/mj2',
        2 => 'image/jpx',
        3 => 'image/jpm',
      ],
      'tiff' => [
        0 => 'image/tiff',
      ],
      'eml' => [
        0 => 'message/rfc822',
      ],
      'pem' => [
        0 => 'application/x-x509-user-cert',
        1 => 'application/x-pem-file',
      ],
      'p10' => [
        0 => 'application/x-pkcs10',
        1 => 'application/pkcs10',
      ],
      'p12' => [
        0 => 'application/x-pkcs12',
      ],
      'p7a' => [
        0 => 'application/x-pkcs7-signature',
      ],
      'p7c' => [
        0 => 'application/pkcs7-mime',
        1 => 'application/x-pkcs7-mime',
      ],
      'p7r' => [
        0 => 'application/x-pkcs7-certreqresp',
      ],
      'p7s' => [
        0 => 'application/pkcs7-signature',
      ],
      'crt' => [
        0 => 'application/x-x509-ca-cert',
        1 => 'application/pkix-cert',
      ],
      'crl' => [
        0 => 'application/pkix-crl',
        1 => 'application/pkcs-crl',
      ],
      'pgp' => [
        0 => 'application/pgp',
      ],
      'gpg' => [
        0 => 'application/gpg-keys',
      ],
      'rsa' => [
        0 => 'application/x-pkcs7',
      ],
      'ics' => [
        0 => 'text/calendar',
      ],
      'zsh' => [
        0 => 'text/x-scriptzsh',
      ],
      'cdr' => [
        0 => 'application/cdr',
        1 => 'application/coreldraw',
        2 => 'application/x-cdr',
        3 => 'application/x-coreldraw',
        4 => 'image/cdr',
        5 => 'image/x-cdr',
        6 => 'zz-application/zz-winassoc-cdr',
      ],
      'wma' => [
        0 => 'audio/x-ms-wma',
      ],
      'vcf' => [
        0 => 'text/x-vcard',
      ],
      'srt' => [
        0 => 'text/srt',
      ],
      'vtt' => [
        0 => 'text/vtt',
      ],
      'ico' => [
        0 => 'image/x-icon',
        1 => 'image/x-ico',
        2 => 'image/vnd.microsoft.icon',
      ],
      'csv' => [
        0 => 'text/x-comma-separated-values',
        1 => 'text/comma-separated-values',
        2 => 'application/vnd.msexcel',
      ],
      'json' => [
        0 => 'application/json',
        1 => 'text/json',
      ],
    ];
}
