# ── Build ─────────────────────────────────────────────────────
FROM node:20-alpine AS builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

ARG VITE_API_URL
ARG VITE_STRIPE_KEY
ARG VITE_PUSHER_APP_KEY
ARG VITE_PUSHER_APP_CLUSTER

ENV VITE_API_URL=$VITE_API_URL
ENV VITE_STRIPE_KEY=$VITE_STRIPE_KEY
ENV VITE_PUSHER_APP_KEY=$VITE_PUSHER_APP_KEY
ENV VITE_PUSHER_APP_CLUSTER=$VITE_PUSHER_APP_CLUSTER

RUN npm run build

# ── Producción ────────────────────────────────────────────────
FROM nginx:alpine AS production

COPY --from=builder /app/dist       /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]