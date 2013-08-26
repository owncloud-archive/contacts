#!/bin/bash

WORKDIR=$PWD
echo "Work directory: $WORKDIR"
cd ..
git clone https://github.com/owncloud/core
cd core
git submodule update --init
mkdir apps2
ln -s $WORKDIR apps2
cd -
git clone https://github.com/owncloud/appframework
ln -s $PWD/appframework apps2
cd $WORKDIR