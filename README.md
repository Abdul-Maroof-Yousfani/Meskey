# Project Initial Setup Guide

Welcome to the project! Follow these steps to set up the application on your local machine.

---

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP** (version 8.0 or higher)
- **Composer** (for dependency management)
- **Node.js** (for frontend dependencies)
- **MySQL** or any other supported database
- **Git** (for version control)


## Custom Artisan Command

This project includes a custom Artisan command to simplify the setup process. Run the following command:

```bash
php artisan app:setup
```

### What This Command Does:
1. Creates required storage folders if they don't exist.
2. Deletes the `public/storage` folder if it exists.
3. Asks for confirmation before proceeding.
4. Runs `migrate:fresh`, `db:seed`, and `storage:link`.

---
### Or Follow:


## Installation Steps

1. **Clone the Repository**  
   Run the following command to clone the project:

   ```bash
   git clone https://github.com/your-username/your-repo-name.git
   cd your-repo-name
   ```

2. **Install PHP Dependencies**  
   Install all PHP dependencies using Composer:

   ```bash
   composer install
   ```

3. **Install JavaScript Dependencies**  
   Install all frontend dependencies using npm:

   ```bash
   npm install
   ```

4. **Set Up Environment File**  
   Copy the `.env.example` file to `.env` and update the database credentials:

   ```bash
   cp .env.example .env
   ```

   Edit the `.env` file and update the following lines:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

5. **Generate Application Key**  
   Generate a unique application key:

   ```bash
   php artisan key:generate
   ```

6. **Run Migrations and Seeders**  
   Set up the database and seed initial data:

   ```bash
   php artisan migrate --seed
   ```

7. **Link Storage Folder**  
   Create a symbolic link for the storage folder:

   ```bash
   php artisan storage:link
   ```

8. **Run the Application**  
   Start the development server:

   ```bash
   php artisan serve
   ```

   Access the application at:  
   [http://localhost:8000](http://localhost:8000)

---

## Warnings and Important Notes

1. **Database Backup**  
   Before running `php artisan migrate:fresh`, ensure you have a backup of your database. This command will delete all existing tables and data.

2. **Environment Configuration**  
   Never share your `.env` file publicly. It contains sensitive information like database credentials and API keys.

3. **Storage Folder**  
   The `public/storage` folder is linked to `storage/app/public`. Deleting or modifying files in this folder may affect the application.

4. **Seeders**  
   Running seeders will populate the database with dummy data. Avoid running seeders in production.

5. **Composer Autoload**  
   If you add new classes or modify the namespace, run the following command to update the autoloader:

   ```bash
   composer dump-autoload
   ```

6. **Node.js Version**  
   Ensure you are using a compatible version of Node.js. Check the `package.json` file for the required version.

---



## Troubleshooting

- **Composer Installation Issues**  
  If you encounter issues during `composer install`, try clearing the Composer cache:

  ```bash
  composer clear-cache
  ```

- **Database Connection Issues**  
  Double-check your `.env` file for correct database credentials. Ensure your database server is running.

- **Permission Issues**  
  If you face permission issues with the `storage` or `bootstrap/cache` folders, run:

  ```bash
  sudo chmod -R 775 storage bootstrap/cache
  ```

---


