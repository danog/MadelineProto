#!/usr/bin/env python3
import subprocess
from struct import unpack

a = subprocess.Popen(["php", "tests/danog/PHP/php2py.php"], stdout=subprocess.PIPE).communicate()[0]
print(unpack('2cxbxBx?xhxHxixIxlxLxqxQxfxdx2xsx5pP', a))
