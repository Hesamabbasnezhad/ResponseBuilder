# 🎯 Laravel API Response Builder

A reusable and standardized response builder for Laravel APIs that provides consistent JSON structures, automatic pagination metadata, and centralized error handling.

This package consists of two core classes:

- ✅ `ResponseBuilder`: Generates unified JSON responses.
- 🧠 `HTTPMessage`: Maps HTTP status codes to human-readable messages.

---

## 🚀 Features

- ✅ Standardized `success`, `created`, `unauthorized`, and `error` responses
- 📦 Compatible with `JsonResource` and Laravel `Paginator`
- 🔁 Automatically adds pagination meta info
- 🧠 Built-in status code message mapping
- 🧼 Keeps controllers clean and DRY

---

## 📦 File Structure

Place these files in your Laravel app directory:

app/Services/ResponseBuilder.php



