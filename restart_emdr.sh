#!/bin/bash

# kill existing emdr process
pkill -f emdr.php

# get absolute path of iveeCore directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# start new emdr process
nohup php "$DIR"/emdr.php > /dev/null 2>&1 &