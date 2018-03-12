# Ubuntu installation

To install MadelineProto dependencies on `Ubuntu`, `Debian`, `Devuan`, or any other `Debian-based` distro, run the following command in your command line:

```
sudo apt-get install python-software-properties software-properties-common
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.2 php7.2-dev php7.2-fpm php7.2-curl php7.2-xml php7.2-zip php7.2-gmp git -y
```

Next, follow the instructions on voip.madelineproto.xyz and prime.madelineproto.xyz to install libtgvoip and PrimeModule.
