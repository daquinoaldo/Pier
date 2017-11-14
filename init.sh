#!/bin/bash
# Where to put files	!! CHANGE IT ALSO IN docker-clean.sh !!
sites_folder="/sites"
rootpw="r00t"
domain="dockerwebserverbuilder.ml"


images=( ubuntu jwilder/nginx-proxy stilliard/pure-ftpd:hardened mysql phpmyadmin/phpmyadmin httpd:2.4 php:apache nginx mysql:5.7 wordpress )


SKIP=false

# Options:
# -s|--skip: skip pull
# -f|--sites_folder: specify the sites folder		!! Remember to change it also in the others file !!
# -p|--rootpw: specify the mysql root password		!! Remember to change it also in the others file !!
while [[ $@ ]]
do
	key="$1"
	case $key in
		-s|--skip)
			SKIP=true
			;;
		-f|--sites_folder)
			sites_folder="$2"
			shift
			;;
		-p|--rootpw)
			rootpw="$2"
			shift
			;;
		*)
			echo "Unknown option $key."
			;;
	esac
	shift
done

function check {
	if [ $? = 0 ]
	then
		echo "done."
	else
		echo "error. Check the logs in the log folder. Error code: $?."
	fi
}

mkdir log

if [ $SKIP == false ]
then
	# Pull the images
	echo "Pulling images..."
	mkdir log/images
	for var in "${images[@]}"
	do
		printf " - Pulling ${var}... "
		plain_var=${var//\//_}
		docker pull ${var} 1>log/images/${plain_var}.log 2>log/images/${plain_var}.error
		check
	done
fi

# Create the sites folder
printf "Coping the sites folder... "
cp -r sites $sites_folder
chmod -R 777 $sites_folder
check

# Make executable this and the docker-clean.sh scripts
#chmod +x init.sh
#chmod +x docker-clean.sh
chmod -R 777 .	#TODO: maybe not??

# Create container for nginx-proxy, ftp, MySQL and phpMyAdmin
printf "Creating container for nginx-proxy... "
docker run --name nginx-proxy -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock:ro jwilder/nginx-proxy 1>log/nginx-proxy.log 2>log/nginx-proxy.error
check
printf "Creating container for ftp... "
docker build -t daquinoaldo/ftp -f Dockerfile.ftp . 1>log/dockerfile.ftp.log 2>log/dockerfile.ftp.error
docker run -d --name ftp -p 21:21 -p 30000-30009:30000-30009 -e "PUBLICHOST=localhost" -e VIRTUAL_HOST="ftp.$domain" -v $sites_folder:$sites_folder daquinoaldo/ftp 1>log/ftp.log 2>log/ftp.error	#TODO: remove hardened
check
printf "Creating container for MySQL... "
docker run --name mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=$rootpw mysql 1>log/mysql.log 2>log/mysql.error &
check
#wait mysql container
until nc -z -v -w30 127.0.0.1 3306 1>/dev/null 2>/dev/null
do
	printf "Waiting for database connection... "
	sleep 5
done
echo "database online."
printf "Creating container for phpMyAdmin... "
docker run --name phpmyadmin --link mysql:db -p 8888:80 -e VIRTUAL_HOST="phpmyadmin.$domain" phpmyadmin/phpmyadmin 1>log/phpmyadmin.log 2>log/phpmyadmin.error &
check

# Preparing php:apache-mysql
printf "Preparing php:apache-mysql... "
docker build -t daquinoaldo/php:apache-mysql -f Dockerfile.builder . 1>log/dockerfile.mysql.log 2>log/dockerfile.mysql.error
check

# Run MySQL container for the builder users and websites database
printf "Run builder-mysql... "
docker run --name builder-mysql -p 8000:3306 -e MYSQL_ROOT_PASSWORD=$rootpw -v `pwd`/sql-initdb.d:/docker-entrypoint-initdb.d -d mysql 1>log/builder-mysql.log 2>log/builder-mysql.error &
check

# Build and run the builder
printf "Preparing builder... "
docker build -t daquinoaldo/builder -f Dockerfile.builder . 1>log/dockerfile.builder.log 2>log/dockerfile.builder.error
check
printf "Run builder... "
docker run --name builder -v /var/run/docker.sock:/var/run/docker.sock -v `pwd`/builder:/var/www/site -v $sites_folder:$sites_folder -p 8080:80 -p 2121:21 -p 2020:20 -p 2222:22 -e VIRTUAL_HOST="builder.$domain" daquinoaldo/builder 1>log/builder.log 2>log/builder.error &
check
echo "All done."
echo "Ready!"
