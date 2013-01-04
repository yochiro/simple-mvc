#!/bin/bash

out=$1;
shift;
for i in $@;
do
if [ ${i##*.} == 'phtml' ]; then
  sed -n '/^---$/,/^---$/ {p}' $i;
elif [ ${i##*.} == 'yaml' ]; then
  cat $i;
fi;
done | xgettext.pl -o `basename $out` -p `dirname $out` -P yaml=*;
