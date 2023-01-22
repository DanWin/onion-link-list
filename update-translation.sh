#!/bin/bash
xgettext --from-code UTF-8 -o locale/onion-link-list.pot `find . -iname '*.php'`
for translation in `find locale -iname '*.po'`; do msgmerge -U "$translation" locale/onion-link-list.pot; msgfmt -o ${translation:0:-2}mo "$translation"; done
