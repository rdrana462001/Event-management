FROM php:8.2-apache

# MySQL સપોર્ટ માટે
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Apache સર્વરને લિસ્ટમાં index.php ચેક કરવા જણાવવા માટે
RUN echo "DirectoryIndex index.php index.html Index.php" >> /etc/apache2/apache2.conf

# પ્રોજેક્ટના તમામ ફાઈલોને /var/www/html/ માં કોપી કરવા માટે
COPY . /var/www/html/

# યોગ્ય ફાઈલ પરમિશન્સ આપવા માટે
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
