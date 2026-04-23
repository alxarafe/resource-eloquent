#!/bin/bash
# Description: Automatically fixes coding standard violations using PHPCBF.

echo "Running PHP Code Beautifier and Fixer..."

docker exec alxarafe-eloquent ./vendor/bin/phpcbf --standard=phpcs.xml src tests -s || true
