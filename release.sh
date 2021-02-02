#!/bin/bash

declare -a trunkDirs=(
  "assets"
  "includes"
  "languages"
)

declare -a trunkFiles=(
  "SoisyClient.php"
  "soisy-woocommerce-plugin.php"
  "readme.txt"
)

declare -a assetsFiles=(
  "assets/screenshot-1.png"
  "assets/screenshot-2.png"
  "assets/screenshot-3.png"
  "assets/icon-256x256.png"
)

rm -rf svn

mkdir -p svn/assets
mkdir -p svn/tags
mkdir -p svn/trunk

for i in "${trunkDirs[@]}"
do
  cp -R "$i" svn/trunk
done

for i in "${trunkFiles[@]}"
do
  cp "$i" svn/trunk
done

for i in "${assetFiles[@]}"
do
  cp "$i" svn/trunk
done

cp -rip svn/trunk svn/tags/5.1.0