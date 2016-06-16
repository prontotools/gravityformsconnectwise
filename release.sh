#!/bin/sh

# The script updates the Wordpress.org SVN repository after pushing
# the latest release from Github
# Credit: https://guh.me/how-to-publish-a-wordpress-plugin-from-github
# Semantic Versioning: http://semver.org/

GITHUB_PLUGIN_NAME=gravityformsconnectwise
WP_PLUGIN_NAME=connectwise-forms-integration
BASE_DIR=`pwd`
TMP_DIR=$BASE_DIR/tmp

mkdir $TMP_DIR
svn co http://plugins.svn.wordpress.org/$WP_PLUGIN_NAME/ $TMP_DIR

cp assets/* $TMP_DIR/assets/
cd $TMP_DIR
svn add assets/* --force
cd ..

cd $TMP_DIR/trunk
git clone --recursive https://github.com/prontotools/$GITHUB_PLUGIN_NAME.git tmp
cp -r tmp/* .
rm -rf tmp
rm -rf .git*
version=`head -n 1 VERSION`
cd $TMP_DIR
svn add trunk/* --force
svn ci -m "Release $version"

if [ -e tags/$version ]
then
  cp -r trunk/* tags/$version
  svn add tags/$version --force
else
  svn cp trunk tags/$version
fi
svn ci -m "Tagging version $version"
rm -rf $TMP_DIR