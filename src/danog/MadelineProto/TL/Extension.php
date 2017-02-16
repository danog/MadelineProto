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

    public function upload($file, $file_name = '', $cb = null)
    {
        if (!file_exists($file)) {
            throw new \danog\MadelineProto\Exception('Given file does not exist!');
        }
        if (empty($file_name)) {
            $file_name = basename($file);
        }
        $file_size = filesize($file);
