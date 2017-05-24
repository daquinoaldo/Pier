#!/bin/bash

# Where to put files	!! CHANGE IT ALSO IN init.sh !!
sites_folder="/sites"


function check {
	if [ $? = 0 ]
	then
		echo "done."
	else
		echo "error. Error code: $?"
	fi
}

echo "Sites folder: \"$sites_folder\""

# Stop and destroy all containers
printf "Stopping all containers... "
docker stop $(sudo docker ps -a -q) 1>/dev/null
check
printf "Destroing all containers... "
docker rm $(sudo docker ps -a -q) 1>/dev/null
check

# Remove logs
printf "Removing logs... "
rm -rf log
check


# Reset port to default
printf "Resetting the start port... "
echo "8000" > www/port
check

# Remove all sites data
printf "Removing all sites data... "
rm -rf /sites
check
