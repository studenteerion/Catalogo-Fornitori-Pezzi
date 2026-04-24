# Usa immagine ufficiale PHP con Apache
FROM tomsik68/xampp

# Copia il codice dell'app nella directory web di Apache
COPY . /www

# Espone la porta 80
EXPOSE 80