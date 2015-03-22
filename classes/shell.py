import os, cmd

class TelepyShell(cmd.Cmd):
  intro='Welcome to telepy interactive shell. Type help or ? for help.\n'
  prompt='>'

  def preloop(self):
    from classes.telepy import Telepy
    self._telepy = Telepy()
  def precmd(self, line):
    # convert first word(command name) to lower and return it as line
    line = line.lstrip()
    blank_pos = line.find(' ')
    if blank_pos < 0: return line.lower()
    return line[:blank_pos].lower() + ' ' + line[blank_pos+1:]
  def completedefault(self, *ignored):
    print(ignored)
  def complete(self, text, state):
    self.super().complete(text, state)
    print('completing')

  def do_shell(self, line):
    '''
    shell <command-line>
    lets you use external shell. !<command-line> for short-hand.
    '''
    print(os.popen(line).read())
  #detailed commands
  def do_msg(self, arg):
    '''
    msg <peer>
    sends message to this peer
    '''
    pass
  def do_fwd(self, arg):
    '''
    fwd <user> <msg-no>
    forward message to user. You can see message numbers starting client with -N
    '''
    pass
  def do_chat_with_peer(self, arg):
    '''
    chat_with_peer <peer>
    starts one on one chat session with this peer. /exit or /quit to end this mode.
    '''
    pass
  def do_add_contact(self, arg):
    '''
    add_contact <phone-number> <first-name> <last-name>
    tries to add contact to contact-list by phone
    '''
    pass
  def do_rename_contact(self, arg):
    '''
    rename_contact <user> <first-name> <last-name>
    tries to rename contact. If you have another device it will be a fight
    '''
    pass
  def do_mark_read(self, arg):
    '''
    mark_read <peer>
    mark read all received messages with peer
    '''
    pass
  def do_delete_msg(self, arg):
    '''
    delete_msg <msg-no>
    deletes message (not completly, though)
    '''
    pass
  def do_restore_msg(self, arg):
    '''
    restore_msg <msg-no>
    restores delete message. Impossible for secret chats. Only possible short time (one hour, I think) after deletion
    '''
    pass

  def do_send_photo(self, arg):
    '''
    send_photo <peer> <photo-file-name>
    sends photo to peer
    '''
    pass

  def do_send_video(self, arg):
    '''
    send_video <peer> <video-file-name>
    sends video to peer
    '''
    pass
  def do_send_text(self, arg):
    '''
    send_text <peer> <text-file-name>
    sends text file as plain messages
    '''
    pass

  def do_load_photo(self, arg):
    '''
    load_photo <msg-no>
    loads photo to download dir
    '''
    pass

  def do_load_video(self, arg):
    '''
    load_video <msg-no>
    loads video to download dir
    '''
    pass
  def do_load_video_thumb(self, arg):
    '''
    load_video_thumb <msg-no>
    loads video thumbnail to download dir
    '''
    pass
  def do_load_audio(self, arg):
    '''
    load_audio <msg-no>
    loads audio to download dir
    '''
    pass
  def do_load_document(self, arg):
    '''
    load_document <msg-no>
    loads document to download dir
    '''
    pass
  def do_load_document_thumb(self, arg):
    '''
    load_document_thumb <msg-no>
    loads document thumbnail to download dir
    '''
    pass

  def do_view_photo(self, arg):
    '''
    view_photo <msg-no>
    loads photo/video to download dir and starts system default viewer
    '''
    pass

  def do_view_video(self, arg):
    '''
    view_video <msg-no>
    '''
    pass
  def do_view_video_thumb(self, arg):
    '''
    view_video_thumb <msg-no>
    '''
    pass
  def do_view_audio(self, arg):
    '''
    view_audio <msg-no>
    '''
    pass
  def do_view_document(self, arg):
    '''
    view_document <msg-no>
    '''
    pass
  def do_view_document_thumb(self, arg):
    '''
    view_document_thumb <msg-no>
    '''
    pass

  def do_fwd_media(self, arg):
    '''
    fwd_media <msg-no>
    send media in your message. Use this to prevent sharing info about author of media (though, it is possible to determine user_id from media itself, it is not possible get access_hash of this user)
    '''
    pass
  def do_set_profile_photo(self, arg):
    '''
    set_profile_photo <photo-file-name>
    sets userpic. Photo should be square, or server will cut biggest central square part
    '''
    pass

  def do_chat_info(self, arg):
    '''
    chat_info <chat>
    prints info about chat
    '''
    arg=arg.split()
    if len(arg) is 1:
      print ('chat_info called with ', arg[0])
  def do_chat_add_user(self,arg):
    '''
    chat_add_user <chat> <user>
    add user to chat
    '''
    print(arg)
  def do_chat_del_user(self,arg):
    '''
    chat_del_user <chat> <user>
    remove user from chat
    '''
    pass
  def do_chat_rename(self,arg):
    '''
    chat_rename <chat> <new-name>
    rename chat room
    '''
    arg=arg.split()

  def do_create_group_chat(self, chat_topic, user1, user2, user3):
    '''
    create_group_chat <chat topic> <user1> <user2> <user3> ...
    creates a groupchat with users, use chat_add_user to add more users
    '''
    print(chat_topic)
    print(user1,user2,user3)

    pass
  def do_chat_set_photo(self, chat, photo):
    '''
    chat_set_photo <chat> <photo-file-name>
    sets group chat photo. Same limits as for profile photos.
    '''
    pass

  def do_search(self, pattern):
    '''
    search <peer> <pattern>
    searches pattern in messages with peer
    '''
    pass
  def do_global_search(self, pattern):
    '''
    global_search <pattern>
    searches pattern in all messages
    '''
    pass

  def do_create_secret_chat(self, user):
    '''
    create_secret_chat <user>
    creates secret chat with this user
    '''
    pass
  def do_visualize_key(self, secret_chat):
    '''
    visualize_key <secret_chat>
    prints visualization of encryption key. You should compare it to your partner's one
    '''
    pass
  def do_set_ttl(self, secret_chat, ttl):
    '''
    set_ttl <secret_chat> <ttl>
    sets ttl to secret chat. Though client does ignore it, client on other end can make use of it
    '''
    pass
  def do_accept_secret_chat(self, secret_chat):
    '''
    accept_secret_chat <secret_chat>
    manually accept secret chat (only useful when starting with -E key)
    '''
    pass

  def do_user_info(self, user):
    '''
    user_info <user>
    prints info about user
    '''
    pass
  def do_history(self, peer, limit=40):
    '''
    history <peer> [limit]
    prints history (and marks it as read). Default limit = 40
    '''
    if peer is '':
      print('no peer have specified')
      return
    args = peer.split()
    if len(args) not in (1,2) :
      print('not appropriate number of arguments : ', peer)
      return
    if len(args) is 2:
      if not args[1].isdecimal() or int(args[1]) < 1:
        print('not a valid limit:', args[1])
      limit = int(args[1])
    print(peer)
    print(limit)
  def do_dialog_list(self, ignored):
    '''
    dialog_list
    prints info about your dialogs
    '''
    pass
  def do_contact_list(self, ignored):
    '''
    contact_list
    prints info about users in your contact list
    '''
    pass
  def do_suggested_contacts(self, ignored):
    '''
    suggested_contacts
    print info about contacts, you have max common friends
    '''
    pass
  def do_stats(self, ignored):
    '''
    stats
    just for debugging
    '''
    pass

  def do_export_card(self, card):
    '''
    export_card
    print your 'card' that anyone can later use to import your contact
    '''
    pass
  def do_import_card(self, card):
    '''
    import_card <card>
    gets user by card. You can write messages to him after that.
    '''
    pass

  def do_quit_force(self, ignored):
    '''
    quit_force
    quit without waiting for query ends
    '''
    return True
  def do_quit(self, ignored):
    '''
    quit
    wait for all queries to end then quit
    '''
    #TODO:safely end queries
    return True