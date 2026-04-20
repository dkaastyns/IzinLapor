#!/bin/bash
# Script build Vercel
# vendor/ sudah di-commit ke repo (PHP tidak tersedia di build phase Vercel)
# Script ini hanya perlu build frontend assets

set -e

echo "=== [1/2] Install npm dependencies ==="
npm ci --prefer-offline

echo "=== [2/2] Build frontend assets (Vite) ==="
npm run build

echo "=== Build selesai! ==="
