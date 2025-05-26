# Snow Mock Server

## Error: Class "SQLite3" not found

If you encounter the error `Class "SQLite3" not found`, it means that the SQLite3 PHP extension is not enabled in your PHP environment. Here are two ways to resolve this issue:

### Option 1: Run with Docker (Recommended)

The project has been configured to use Docker with the SQLite3 extension enabled. To run the application with Docker:

1. Make sure you have Docker and Docker Compose installed on your system
2. Open a terminal in the project root directory
3. Run the following command:

```
docker-compose up -d
```

This will build a Docker image with SQLite3 support and start the application.

### Option 2: Enable SQLite3 in PHPStorm's PHP Configuration

If you prefer to run the application directly through PHPStorm without Docker, you need to enable the SQLite3 extension in your PHP configuration:

#### For Windows:

1. Locate your PHP installation directory (e.g., `C:\xampp\php` or `C:\php`)
2. Find the `php.ini` file in that directory
3. Open the file in a text editor
4. Search for `;extension=sqlite3` (note the semicolon at the beginning)
5. Remove the semicolon to uncomment the line, so it becomes `extension=sqlite3`
6. Save the file
7. Restart PHPStorm and any running PHP servers

#### For macOS/Linux:

1. Find your PHP configuration file with `php --ini`
2. Open the php.ini file in a text editor
3. Search for `;extension=sqlite3`
4. Remove the semicolon to uncomment the line
5. Save the file
6. Restart PHPStorm and any running PHP servers

### Verifying SQLite3 is Enabled

To verify that SQLite3 is properly enabled, you can create a simple PHP file with the following content:

```php
<?php
phpinfo();
```

Run this file and search for "sqlite" on the page. You should see a section for SQLite3 if it's properly enabled.

## Project Structure

This project is a mock server for demonstrating ITSM system flows, including asset reservation and return processes.
