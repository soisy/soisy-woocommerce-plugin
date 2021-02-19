  #!/bin/bash

die () {
    echo >&2 "$@"
    exit 1
}

[ "$#" -eq 1 ] || die "1 argument required, $# provided"
echo $1 | grep -E -q '^[0-9\.]+$' || die "Semantic version argument required, $1 provided"

rm -rf svn
mkdir svn
cd svn

svn checkout --depth immediates https://plugins.svn.wordpress.org/soisy-pagamento-rateale .
svn update --set-depth infinity .

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

for i in "${trunkDirs[@]}"
do
  cp -R "../$i" trunk
done

for i in "${trunkFiles[@]}"
do
  cp "../$i" trunk
done

for i in "${assetsFiles[@]}"
do
  cp "../$i" assets
done

sed -ie "s/\${VERSION}/$1/" trunk/readme.txt

cp -rip trunk tags/$1
