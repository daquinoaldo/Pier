docker ps --format '{{.Names}}' | sed '/nginx-proxy/d' | sed '/builder/d'
