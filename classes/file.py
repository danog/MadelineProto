from sys import platform
import os
from subprocess import call
# from os import path  # path.exists() may used in future.. may be..

class File():
  def __init__(self, path):
    self._path = path

  def write_bytes(self, bytes):
    '''  truncates the file and create new with contents :param bytes '''
    with open(self._path, 'r+b') as file:
      file.write(bytes)

  def read_bytes(self):
    ''' read the file as bytes '''
    # buf = b''
    with open(self._path, 'r+b') as file:
      return file.read()  # is this safe?
      # buf = file.read()
    # return buf

  def open(self):
    '''tries to open with os default viewer'''
    call(('cmd /c start "" "'+ self._path +'"')if os.name is 'nt' else ('open' if platform.startswith('darwin') else 'xdg-open', self._path))

  def remove(self):
    ''' try to remove the file '''
    try:
      os.remove(self._path)
    except FileNotFoundError: pass