FROM 192.168.69.1:5000/php-cli

RUN curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg -o /usr/share/keyrings/githubcli-archive-keyring.gpg && \
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" > /etc/apt/sources.list.d/github-cli.list && \
    apt-get update && \
    bash -ec 'apt-get -y install php{8.{0,1},7.{1..4}}-{cli,curl,gmp,igbinary,mbstring,pgsql,readline,redis,pdo-mysql,xml,zip} php7.4-ffi php8.0-ffi php8.1-ffi procps git unzip gh' && \
    apt-get -y autoremove && \
    apt-get clean && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
    composer self-update --2.2 && \
    bash -c 'for f in {8.{0,1},7.{1..4}}; do mkdir -p ~/.local/php/$f; ln -s $(which php$f) ~/.local/php/$f/php;done' && \
    sed 's/;phar.readonly = On/phar.readonly = 0/g' -i /etc/php/*/cli/php.ini
