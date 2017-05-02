# mkdir sites/$1
# chmod 777 sites/$1
cp -r sites/default sites/$1
docker run --name $1 -p 8006:80 -v `pwd`/sites/$1:/usr/share/nginx/html:ro -e VIRTUAL_HOST=$1 nginx 1>/dev/null &
