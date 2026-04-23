#!/bin/bash
# Description: Runs static analysis tools (PHPStan, Psalm).

echo "Running PHPStan..."
docker exec alxarafe-eloquent ./vendor/bin/phpstan analyse src --memory-limit=1G

echo "Running Psalm..."
docker exec alxarafe-eloquent ./vendor/bin/psalm src --output-format=console
