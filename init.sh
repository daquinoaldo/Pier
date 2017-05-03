# Where to put files	!! CHANGE IT ALSO IN docker-clean.sh !!
sites_folder="/sites"

# Create the sites folder
cp -r sites $sites_folder
chmod 777 $sites_folder

# Make executable this and the docker-clean.sh scripts
sudo chmod +x init.sh
sudo chmod +x docker-clean.sh

# Create container for nginx-proxy
docker run --name nginx-proxy -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock:ro jwilder/nginx-proxy

# Pull the images
docker pull nginx
docker pull httpd:2.4
docker pull mysql:5.7
docker pull wordpress

# build and run the builder
docker build -t builder .
docker run --name builder -v /var/run/docker.sock:/var/run/docker.sock -v `pwd`/www:/var/www/site -v $sites_folder:$sites_folder -p 8080:80 -e VIRTUAL_HOST=builder.aldodaquino.ml builder &
