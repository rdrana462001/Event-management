FROM php:8.2-apache

# MySQL સપોર્ટ માટે
RUN docker-php-ext-install mysqli pdo pdo_mysql

# આ લાઇન Apache સર્વરને જણાવશે કે લિસ્ટમાં Index.php પણ ચેક કરે (સુરક્ષા માટે)
RUN echo "DirectoryIndex index.php index.html Index.php" >> /etc/apache2/apache2.conf

# તમારો બધો કોડ સર્વરમાં કોપી કરવા માટે
COPY . /var/www/html/

EXPOSE 80
