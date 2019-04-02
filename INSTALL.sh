#!/bin/bash

set -e

./bin/install-wp-tests.sh soisy_test root soisy mysql 4.9

echo -e "\e[32mInstallation completed.\e[0m"