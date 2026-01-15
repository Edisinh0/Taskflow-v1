# Etapa 1: Construcción
FROM node:20-alpine as build-stage

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

# Copiar archivo de configuración de producción
COPY .env.production .env.production

RUN npm run build

# Etapa 2: Producción con Nginx
FROM nginx:alpine as production-stage

# Copiar configuración personalizada de Nginx para Vue (SPA)
# Creamos este archivo en el siguiente paso
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copiar los archivos construidos desde la etapa anterior
COPY --from=build-stage /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
