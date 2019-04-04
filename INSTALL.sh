#!/bin/bash

set -e

./bin/install-wp-tests.sh soisy_test root soisy mysql 4.9

COLOR_GREEN="\e[32m"
NO_COLOR="\e[0m"

echo -e "$COLOR_GREEN"
echo "Installation completed."
echo -e "$NO_COLOR"