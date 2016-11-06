#!/usr/bin/env bash

set -e

cd repository

if [ -f .git/HEAD.lock ];
then
    rm .git/HEAD.lock
fi
git reset --hard master

for commit in $(git rev-list master)
do
    git checkout $commit 2>&1 > /dev/null
    count=$(find . -maxdepth 8 -type f | wc -l | xargs)
    echo $count
    git show -s --format=%ct $commit | awk -v count="$count" '{ print "php-di.dirnum " count " " $1 }' | nc localhost -c 2003
done
