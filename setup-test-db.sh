#!/bin/bash

# Script untuk setup database testing
# Jalankan script ini sebelum menjalankan test

echo "🔧 Setting up testing database..."

# Buat database testing jika belum ada
mysql -u root -e "CREATE DATABASE IF NOT EXISTS arabica_testing;" 2>/dev/null || \
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS arabica_testing;" 2>/dev/null || \
echo "⚠️  Gagal membuat database. Pastikan MySQL sudah running dan credentials benar."

echo "✅ Database arabica_testing siap!"
echo ""
echo "📝 Untuk menjalankan test:"
echo "   ./vendor/bin/pest"
echo "   atau"
echo "   composer test"
