#!/bin/sh

cp contrib/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
cp contrib/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push
