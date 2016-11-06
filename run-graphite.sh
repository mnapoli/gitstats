#!/usr/bin/env bash

docker run \
  --rm \
  --name graphite \
  -p 8000:80 \
  -p 2003:2003 \
  -v $(pwd)/conf/storage-schemas.conf:/opt/graphite/conf/storage-schemas.conf \
  sitespeedio/graphite
