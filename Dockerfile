FROM php:8.2-apache

# MySQL સપોર્ટ માટે
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Apache સર્વરને લિસ્ટમાં index.php ચેક કરવા જણાવવા માટે
RUN echo "DirectoryIndex index.php index.html Index.php" >> /etc/apache2/apache2.conf

# ડબલ-કોટ્સ વગર પાથ સેટ કરવા (ધ્યાન રાખજો સ્પેસ અને સ્લેશ બરાબર હોવા જોઈએ)
COPY ./event\ management\ system/ /var/www/html/

EXPOSE 80
