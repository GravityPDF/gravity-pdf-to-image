#!/usr/bin/env bash

echo $0 $1 $2

if [ $# -lt 1 ]; then
	echo "usage: $0 <version> [branch]"
	exit 1
fi

VERSION=$1
BRANCH=${2-development}
TMP_DIR="./tmp/package/"
PACKAGE_DIR="${TMP_DIR}${VERSION}"
PACKAGE_NAME="gravity-pdf-to-image"

# Create the working directory
mkdir -p ${PACKAGE_DIR}

# Get an archive of our plugin
git archive ${BRANCH} --output ${PACKAGE_DIR}/package.tar.gz
tar -zxf ${PACKAGE_DIR}/package.tar.gz --directory ${PACKAGE_DIR} && rm ${PACKAGE_DIR}/package.tar.gz

# Run Composer
composer install --ignore-platform-reqs --no-dev  --prefer-dist --optimize-autoloader --working-dir ${PACKAGE_DIR}

# Generate translation file
npm install --global wp-pot-cli
wp-pot --domain gravity-pdf-to-image --src ${PACKAGE_DIR}/src/**/*.php --src ${PACKAGE_DIR}/*.php --package 'Gravity PDF to Image' --dest-file ${PACKAGE_DIR}/languages/gravity-pdf-to-image.pot --relative-to ${PACKAGE_DIR} > /dev/null

# Cleanup additional build files
FILES=(
"${PACKAGE_DIR}/composer.json"
"${PACKAGE_DIR}/composer.lock"
)

for i in "${FILES[@]}"
do
    rm ${i}
done

# Create zip package
cd ${TMP_DIR}
rm -R -f ${PACKAGE_NAME}
mv ${VERSION} ${PACKAGE_NAME}
zip -r -q ${PACKAGE_NAME}-${VERSION}.zip ${PACKAGE_NAME}
mv ${PACKAGE_NAME} ${VERSION}