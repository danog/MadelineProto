# Requirements

MadelineProto requires the `xml`, `gmp`, `curl` extensions to function properly.

To install MadelineProto dependencies on `Ubuntu`, `Debian`, `Devuan`, or any other `Debian-based` distro, run the following command in your command line:

```bash
sudo apt-get install python-software-properties software-properties-common
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.2 php7.2-dev php7.2-fpm php7.2-curl php7.2-xml php7.2-zip php7.2-gmp git -y
```

Next, follow the instructions on [voip.madelineproto.xyz](https://voip.madelineproto.xyz) and [prime.madelineproto.xyz](https://prime.madelineproto.xyz) to install libtgvoip and PrimeModule.

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/FEATURES.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/INSTALLATION.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>