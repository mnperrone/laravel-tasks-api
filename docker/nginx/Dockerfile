FROM nginx:alpine

# Copy custom nginx config
COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
