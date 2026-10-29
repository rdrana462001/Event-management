FROM php:8.2-apache

# MySQL સપોર્ટ માટે
RUN docker-php-ext-install mysqli pdo pdo_mysql

# આ લાઇન Apache સર્વરને જણાવશે કે index.php ક્યાં છે
RUN echo "DirectoryIndex index.php index.html Index.php" >> /etc/apache2/apache2.conf

# તમારા 'event management system' ફોલ્ડરની અંદરનો બધો કોડ સર્વરના મેઈન ફોલ્ડરમાં કોપી કરવા માટે
COPY "./event management system/" /var/www/html/

EXPOSE 80
