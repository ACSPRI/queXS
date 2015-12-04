#!/bin/bash
for f in `find ./ -iname *.po`; do
   BASE=`basename $f .po`
   DIR=`dirname $f`
   grep -v '#, php-format' $f > $DIR/tmpfile
   mv $DIR/tmpfile $f
   msgfmt $f -o $DIR/$BASE.mo
done
