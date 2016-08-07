<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
require_once 'os.php';
class TelepyShell
{
    public $intro = 'Welcome to telepy interactive shell. Type help or ? for help.
';
    public $prompt = '>';

    public function preloop()
    {
        require_once 'classes/telepy.php';
        $this->_telepy = new Telepy();
    }

    public function precmd($line)
    {
        $line = $line->lstrip();
        $blank_pos = $line->find(' ');
        if (($blank_pos < 0)) {
            return $line->lower();
        }

        return array_slice($line, null, $blank_pos)->lower().' '.array_slice($line, ($blank_pos + 1), null);
    }

    public function completedefault(...$ignored)
    {
        pyjslib_printnl($ignored);
    }

    public function complete($text, $state)
    {
        $this->super()->complete($text, $state);
        pyjslib_printnl('completing');
    }

    /**
     * shell <command-line>
     * lets you use external shell. !<command-line> for short-hand.
     */
    public function do_shell($line)
    {
        pyjslib_printnl(os::popen($line)->read());
    }

    /**
     * msg <peer>
     * sends message to this peer.
     */
    public function do_msg($arg)
    {
    }

    /**
     * fwd <user> <msg-no>
     * forward message to user. You can see message numbers starting client with -N.
     */
    public function do_fwd($arg)
    {
    }

    /**
     * chat_with_peer <peer>
     * starts one on one chat session with this peer. /exit or /quit to end this mode.
     */
    public function do_chat_with_peer($arg)
    {
    }

    /**
     * add_contact <phone-number> <first-name> <last-name>
     * tries to add contact to contact-list by phone.
     */
    public function do_add_contact($arg)
    {
    }

    /**
     * rename_contact <user> <first-name> <last-name>
     * tries to rename contact. If you have another device it will be a fight.
     */
    public function do_rename_contact($arg)
    {
    }

    /**
     * mark_read <peer>
     * mark read all received messages with peer.
     */
    public function do_mark_read($arg)
    {
    }

    /**
     * delete_msg <msg-no>
     * deletes message (not completly, though).
     */
    public function do_delete_msg($arg)
    {
    }

    /**
     * restore_msg <msg-no>
     * restores delete message. Impossible for secret chats. Only possible short time (one hour, I think) after deletion.
     */
    public function do_restore_msg($arg)
    {
    }

    /**
     * send_photo <peer> <photo-file-name>
     * sends photo to peer.
     */
    public function do_send_photo($arg)
    {
    }

    /**
     * send_video <peer> <video-file-name>
     * sends video to peer.
     */
    public function do_send_video($arg)
    {
    }

    /**
     * send_text <peer> <text-file-name>
     * sends text file as plain messages.
     */
    public function do_send_text($arg)
    {
    }

    /**
     * load_photo <msg-no>
     * loads photo to download dir.
     */
    public function do_load_photo($arg)
    {
    }

    /**
     * load_video <msg-no>
     * loads video to download dir.
     */
    public function do_load_video($arg)
    {
    }

    /**
     * load_video_thumb <msg-no>
     * loads video thumbnail to download dir.
     */
    public function do_load_video_thumb($arg)
    {
    }

    /**
     * load_audio <msg-no>
     * loads audio to download dir.
     */
    public function do_load_audio($arg)
    {
    }

    /**
     * load_document <msg-no>
     * loads document to download dir.
     */
    public function do_load_document($arg)
    {
    }

    /**
     * load_document_thumb <msg-no>
     * loads document thumbnail to download dir.
     */
    public function do_load_document_thumb($arg)
    {
    }

    /**
     * view_photo <msg-no>
     * loads photo/video to download dir and starts system default viewer.
     */
    public function do_view_photo($arg)
    {
    }

    /**
     * view_video <msg-no>.
     */
    public function do_view_video($arg)
    {
    }

    /**
     * view_video_thumb <msg-no>.
     */
    public function do_view_video_thumb($arg)
    {
    }

    /**
     * view_audio <msg-no>.
     */
    public function do_view_audio($arg)
    {
    }

    /**
     * view_document <msg-no>.
     */
    public function do_view_document($arg)
    {
    }

    /**
     * view_document_thumb <msg-no>.
     */
    public function do_view_document_thumb($arg)
    {
    }

    /**
     * fwd_media <msg-no>
     * send media in your message. Use this to prevent sharing info about author of media (though, it is possible to determine user_id from media itself, it is not possible get access_hash of this user).
     */
    public function do_fwd_media($arg)
    {
    }

    /**
     * set_profile_photo <photo-file-name>
     * sets userpic. Photo should be square, or server will cut biggest central square part.
     */
    public function do_set_profile_photo($arg)
    {
    }

    /**
     * chat_info <chat>
     * prints info about chat.
     */
    public function do_chat_info($arg)
    {
        $arg = $arg->split();
        if ((count($arg) == 1)) {
            pyjslib_printnl(['chat_info called with ', $arg[0]]);
        }
    }

    /**
     * chat_add_user <chat> <user>
     * add user to chat.
     */
    public function do_chat_add_user($arg)
    {
        pyjslib_printnl($arg);
    }

    /**
     * chat_del_user <chat> <user>
     * remove user from chat.
     */
    public function do_chat_del_user($arg)
    {
    }

    /**
     * chat_rename <chat> <new-name>
     * rename chat room.
     */
    public function do_chat_rename($arg)
    {
        $arg = $arg->split();
    }

    /**
     * create_group_chat <chat topic> <user1> <user2> <user3> ...
     * creates a groupchat with users, use chat_add_user to add more users.
     */
    public function do_create_group_chat($chat_topic, $user1, $user2, $user3)
    {
        pyjslib_printnl($chat_topic);
        pyjslib_printnl([$user1, $user2, $user3]);
    }

    /**
     * chat_set_photo <chat> <photo-file-name>
     * sets group chat photo. Same limits as for profile photos.
     */
    public function do_chat_set_photo($chat, $photo)
    {
    }

    /**
     * search <peer> <pattern>
     * searches pattern in messages with peer.
     */
    public function do_search($pattern)
    {
    }

    /**
     * global_search <pattern>
     * searches pattern in all messages.
     */
    public function do_global_search($pattern)
    {
    }

    /**
     * create_secret_chat <user>
     * creates secret chat with this user.
     */
    public function do_create_secret_chat($user)
    {
    }

    /**
     * visualize_key <secret_chat>
     * prints visualization of encryption key. You should compare it to your partner's one.
     */
    public function do_visualize_key($secret_chat)
    {
    }

    /**
     * set_ttl <secret_chat> <ttl>
     * sets ttl to secret chat. Though client does ignore it, client on other end can make use of it.
     */
    public function do_set_ttl($secret_chat, $ttl)
    {
    }

    /**
     * accept_secret_chat <secret_chat>
     * manually accept secret chat (only useful when starting with -E key).
     */
    public function do_accept_secret_chat($secret_chat)
    {
    }

    /**
     * user_info <user>
     * prints info about user.
     */
    public function do_user_info($user)
    {
    }

    /**
     * history <peer> [limit]
     * prints history (and marks it as read). Default limit = 40.
     */
    public function do_history($peer, $limit = 40)
    {
        if (($peer == '')) {
            pyjslib_printnl('no peer have specified');

            return;
        }
        $args = $peer->split();
        if (!in_array(count($args), [1, 2])) {
            pyjslib_printnl(['not appropriate number of arguments : ', $peer]);

            return;
        }
        if ((count($args) == 2)) {
            if (!($args[1]->isdecimal()) || (pyjslib_int($args[1]) < 1)) {
                pyjslib_printnl(['not a valid limit:', $args[1]]);
            }
            $limit = pyjslib_int($args[1]);
        }
        pyjslib_printnl($peer);
        pyjslib_printnl($limit);
    }

    /**
     * dialog_list
     * prints info about your dialogs.
     */
    public function do_dialog_list($ignored)
    {
    }

    /**
     * contact_list
     * prints info about users in your contact list.
     */
    public function do_contact_list($ignored)
    {
    }

    /**
     * suggested_contacts
     * print info about contacts, you have max common friends.
     */
    public function do_suggested_contacts($ignored)
    {
    }

    /**
     * stats
     * just for debugging.
     */
    public function do_stats($ignored)
    {
    }

    /**
     * export_card
     * print your 'card' that anyone can later use to import your contact.
     */
    public function do_export_card($card)
    {
    }

    /**
     * import_card <card>
     * gets user by card. You can write messages to him after that.
     */
    public function do_import_card($card)
    {
    }

    /**
     * quit_force
     * quit without waiting for query ends.
     */
    public function do_quit_force($ignored)
    {
        return true;
    }

    /**
     * quit
     * wait for all queries to end then quit.
     */
    public function do_quit($ignored)
    {
        return true;
    }
}
