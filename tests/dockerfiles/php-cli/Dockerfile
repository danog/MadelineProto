FROM debian:bullseye-slim

RUN apt-get update && apt-get install -y make g++ curl && \
    curl -sSLf https://github.com/danog/PrimeModule-ext/archive/refs/tags/2.0.tar.gz | tar -xz && \
    cd PrimeModule-ext-2.0 && \
    make -j$(nproc) && \
    make install && \
    cd .. && \
    rm -r PrimeModule-ext-2.0 && \
    apt-get remove make g++ -y && \
    curl -sSL https://packages.sury.org/php/README.txt | bash -x && \
    apt-get update && \
    apt-get -y install php8.1-cli \
        php8.1-ffi \
        php8.1-gmp \
        php8.1-igbinary \
        php8.1-mbstring \
        php8.1-opcache \
        php8.1-pdo-mysql \
        php8.1-xml \
        php8.1-zip && \
    apt-get -y autoremove && \
    apt-get clean

ADD ./8.1 /etc/php/8.1

CMD ["php-fpm8.1"]