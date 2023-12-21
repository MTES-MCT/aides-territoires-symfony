#!/bin/bash

echo $JWT_PRIVATE_PEM | base64 -d > ./private.pem
#echo $JWT_PUBLIC_PEM | base64 -d > ./public.pem
# Start default script for PHP apps
$HOME/bin/run
