FROM php:8.2-apache

# જો તમારી એપમાં MySQL ડેટાબેઝ વાપર્યો હોય તો આ લાઈન જરૂરી છે
RUN docker-php-ext-install mysqli pdo pdo_mysql

# તમારા બધા કોડને સર્વરના ફોલ્ડરમાં કોપી કરવા માટે
COPY . /var/www/html/

# Apache સર્વર માટે પોર્ટ ઓપન કરવા
EXPOSE 80
