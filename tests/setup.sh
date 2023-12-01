#!/bin/bash

set -ex

export COMPOSER_PROCESS_TIMEOUT=100000

composer update
