sudo docker run -i --name php-builder -v `pwd`:/var/www/html -p 8182:80 -e VIRTUAL_HOST=builder.aldodaquino.ml -e ServerName=builder.aldodaquino.ml php:7.0-apache
