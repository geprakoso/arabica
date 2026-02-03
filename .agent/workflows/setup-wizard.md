---
description: Sets up the Arabica project environment, installs dependencies, prepares databases, and verifies the installation.
---

# Arabica Project Setup Wizard

This workflow will guide you through setting up the Arabica project on your local machine.

1. **Environment Configuration**
   Copy the example environment file to `.env` if it doesn't already exist.

    ```bash
    cp -n .env.example .env
    ```

    _Note: If .env already exists, this command does nothing._

2. **Install PHP Dependencies**
   Install the required PHP packages using Composer.
   // turbo

    ```bash
    composer install
    ```

3. **Generate Application Key**
   Generate the `APP_KEY` in your `.env` file.

    ```bash
    php artisan key:generate
    ```

4. **Set Up Database**
   Run the database migrations to set up the schema.

    ```bash
    php artisan migrate
    ```

5. **Install Node dependencies**
   Install the required JavaScript/Node packages.
   // turbo

    ```bash
    npm install
    ```

6. **Build Frontend Assets**
   Compile the frontend assets.
   // turbo

    ```bash
    npm run build
    ```

7. **Set Up Test Database**
   Initialize the testing database to ensure tests can run.
   // turbo

    ```bash
    ./setup-test-db.sh
    ```

8. **Verify Installation**
   Run the test suite to confirm the project is set up correctly.

    ```bash
    composer test
    ```

9. **Start Development Server**
   Start the local development server with all services.
    ```bash
    composer run dev
    ```
