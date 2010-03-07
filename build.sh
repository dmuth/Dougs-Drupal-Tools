#!/bin/sh
#
# This script is used to build a file for distribution.
#

#
# Make errors fatal
#
set -e

#
# Debugging
#
#set -x

#
# Grab our highest version, so we can get a uniquely named file.
#
echo "Checking version...  Make sure you have svn set up!"
VERSION=`git svn info |grep "Revision" |cut -d: -f2 |sed -e s/[^0-9]//`

#
# Make a temporary directory
#
TMPDIR=`mktemp -d`
mkdir $TMPDIR/ddt

#
# Copy everything to our temp directory and go to the parent.
# The whole "ddt" directory is what the name of the module directory will 
# be in Drupal
#
cp -r * $TMPDIR/ddt
pushd $TMPDIR

TARBALL=ddt-build_${VERSION}.tgz

#
# We don't want any revision control files included in the atrball.
#
OPTIONS='--exclude RCS --exclude .svn --exclude .git'

#
# Make our tarball
#
tar cfzv ${TARBALL} ddt ${OPTIONS}

popd
mv ${TMPDIR}/${TARBALL} . 
echo "Distfile created in '${TARBALL}'"

#
# Cleanup when we're done
#
rm -rf $TMPDIR

