#!/bin/bash
#Source: http://stackoverflow.com/questions/7496156/gettext-how-to-update-po-and-pot-files-after-the-source-is-modified
echo '' > messages.po # xgettext needs that file, and we need it empty
cd ..
find . -type f -iname "*.php" | xgettext --keyword='T_' --keyword='TQ_' -o locale/messages.po -j -f -
cd locale
msgmerge -N quexs.pot messages.po > new.po
grep -v '#, php-format' new.po  > quexs.pot
rm new.po 
rm messages.po
