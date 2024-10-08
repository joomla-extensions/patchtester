#!/bin/sh
cd ../../
rm -rf build/packaging && mkdir build/packaging
rm -rf build/packages && mkdir build/packages
composer install --no-dev -o
cp -r administrator build/packaging/administrator
cp -r media/com_patchtester build/packaging/media
rm -rf build/packaging/administrator/backups/*.txt
mv build/packaging/administrator/components/com_patchtester/patchtester.xml build/packaging/patchtester.xml
mv build/packaging/administrator/components/com_patchtester/script.php build/packaging/script.php
cd build/packaging
tar jcf ../packages/com_patchtester_"$1".tar.bz2 .
tar zcf ../packages/com_patchtester_"$1".tar.gz .
zip -r ../packages/com_patchtester_"$1".zip .
cd ../../
composer install
php build/patchtester/hash_generator.php
