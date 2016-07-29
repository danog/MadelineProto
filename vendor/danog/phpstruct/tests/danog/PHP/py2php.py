#!/usr/bin/env python3
from struct import pack
import sys
sys.stdout.buffer.write(bytes(pack('2cxbxBx?xhxHxixIxlxLxqxQxfxdx2xsx5pP', b'n', b'v', -127, 100, True, 333, 444, 232423, 234342, 999999999999, 999999999999, -88888888888888,88888888888877, 2.2343, 3.03424, b'df', b'asdfghjkl', 1283912)))
