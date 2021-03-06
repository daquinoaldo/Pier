#!/bin/bash
if [ "$#" -ne 3 ]
then
	echo "You must pass this 3 arguments: username, password, home"
else
	username=$1
	password=$2
	home=$3
	mkdir ${home}
	chmod -R 777 ${home}
	useradd ${username}
	uid="$(id -u ${username})"
	gid="$(id -G ${username})"
	echo ${uid}
	echo ${gid}
	( echo ${password} ; echo ${password} ) | pure-pw useradd ${username} -u ${uid} -g ${gid} -d ${home}
	pure-pw mkdb
fi
