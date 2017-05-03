# Where to put files	!! CHANGE IT ALSO IN init.sh !!
sites_folder="/sites"


# Stop and destroy all containers
sudo docker stop $(sudo docker ps -a -q)
sudo docker rm $(sudo docker ps -a -q)

# Reset port to default
echo "8000" > www/port

# Remove all sites data
sudo rm -rf /sites
