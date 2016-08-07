#CLI like interface

import argparse, getopt, os, io, struct, mtproto
from classes.shell import TelepyShell

if __name__ == '__main__':
  parser = argparse.ArgumentParser('telepy',description='Python implementation of telegram API.')
  parser.add_argument('command', nargs='?', choices=['cmd', 'dialog_list', 'contact_list'] + ['chat_' + sub for sub in ['info', 'add_user', 'add_user_to_chat', 'del_user', 'set_photo', 'rename']])
  parser.add_argument('args', nargs='*')

  #for command, args, help in (('info', 1, 'prints info about chat'), ('add_user', 2, 'add user to chat'), ('del_user', 2, 'remove user from chat'), ('set_photo', 1, 'sets group chat photo. Same limits as for profile photos.')):
  #  parser.add_argument('chat_' + command, nargs=args, help=help)
  #parser.add_argument
  args = parser.parse_args()

  if args.command is None:
    TelepyShell().cmdloop()