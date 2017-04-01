<?php
$payload = base64_decode('NAAAAAAAAAAAAAAAAAAAAAAAAAC06N5YFAAAAHiXRmAwx9DuB28gQszy/RHjhN2RdLkYdQ==');
$socket = fsockopen('tcp://149.154.167.91:443'); // DC 4
$socket = fsockopen('tcp://149.154.167.50:443'); // DC 2
stream_set_timeout($socket, 5);
echo 'Wrote '.fwrite($socket, $payload).' bytes'.PHP_EOL;
if (strlen(fread($socket, 100))) echo 'Read 100 bytes from socket'.PHP_EOL; else echo 'No data could be read from socket'.PHP_EOL;
