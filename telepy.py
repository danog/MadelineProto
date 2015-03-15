#CLI like interface

import argparse, getopt, cmd

parser = argparse.ArgumentParser('telepy',description='Python implementation of telegram API.')
parser.add_argument('command', nargs='?', choices=['cmd', 'dialog_list', 'contact_list'] + ['chat_' + sub for sub in ['info', 'add_user', 'add_user_to_chat', 'del_user', 'set_photo', 'rename']])
parser.add_argument('args', nargs='*')

#for command, args, help in (('info', 1, 'prints info about chat'), ('add_user', 2, 'add user to chat'), ('del_user', 2, 'remove user from chat'), ('set_photo', 1, 'sets group chat photo. Same limits as for profile photos.')):
#  parser.add_argument('chat_' + command, nargs=args, help=help)
#parser.add_argument
args = parser.parse_args()

print(args)

class telepyShell(cmd.Cmd):
  intro='Welcome to telepy interactive shell. Type help or ? for help.\n'
  prompt='>'

  def precmd(self, line):
    # if len(line) < 1 : return None
    # lines = line.split()
    # cmd_name = lines[0].lower()
    return line

  #detailed commands
  def do_chat_info(self, arg):
    arg=arg.split()
    if len(arg) is 1:
      print ('chat_info called with ', arg[0])

  def do_chat_add_user(self,arg):
    print(arg)

  def do_quit(self, arg):
    return True
if args.command is None:
  telepyShell().cmdloop()
# chat_info <chat> -
# chat_add_user <chat> <user> -
# chat_del_user <chat> <user> -
# chat_set_photo <chat> <photo-file-name> -
# rename_chat <chat> <new-name>
# create_group_chat <chat topic> <user1> <user2> <user3> ... - creates a groupchat with users, use chat_add_user to add more users
#     Search
#
# search <peer> pattern - searches pattern in messages with peer
#     global_search pattern - searches pattern in all messages
# Secret chat
#
# create_secret_chat <user> - creates secret chat with this user
# visualize_key <secret_chat> - prints visualization of encryption key. You should compare it to your partner's one
# set_ttl <secret_chat> <ttl> - sets ttl to secret chat. Though client does ignore it, client on other end can make use of it
# accept_secret_chat <secret_chat> - manually accept secret chat (only useful when starting with -E key)
# Stats and various info
#
# user_info <user> - prints info about user
# history <peer> [limit] - prints history (and marks it as read). Default limit = 40
# dialog_list - prints info about your dialogs
# contact_list - prints info about users in your contact list
# suggested_contacts - print info about contacts, you have max common friends
# stats - just for debugging
#     show_license - prints contents of GPLv2
# help - prints this help
# Card
#
# export_card - print your 'card' that anyone can later use to import your contact
# import_card <card> - gets user by card. You can write messages to him after that.
# Other
#
# quit - quit
# safe_quit - wait for all queries to end then quit