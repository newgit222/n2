#!/bin/sh
ps -fe|grep  AutoOrderNotify|grep -v grep
if [ $? -ne 0 ]
then
 nohup ./shell.sh &
else
echo "runing....."
fi
#####
