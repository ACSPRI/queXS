#!/bin/bash
for f in `find ./ -iname 'quexs-*.po'`; do
   BASE=`basename $f .po`
   DIR=`dirname $f`
   L=${BASE:6}
   L=${L,,}
   L=${L/_/-}
   mv $f $L/LC_MESSAGES/$L.po
done
