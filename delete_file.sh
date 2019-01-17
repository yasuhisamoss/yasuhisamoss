#! /bin/bash

function in_array ()
{
idx=0
check_value=1
for func_option in $@; do
    if [ $idx -eq 0 ]; then
        idx=$(( idx + 1 ))
    else
        if [ $1 = ${func_option} ]; then
            check_value=0
        fi
    fi
done
echo ${check_value}
}

dir=$1
# 14日前だがmtimeの仕様により13
list=`find ${dir} -mtime +30 | sed 's!^.*/!!'`
for filepath in $list; do
    `rm -rf ${dir}${filepath}`
done
