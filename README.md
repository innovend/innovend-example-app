# Innovend Example App

## Overview

The Innovend Example App is a demonstration application to help developers get started using Innovend's APIs. Innovend provides smart vending and locker solutions for businesses, and this example app showcases how to integrate with our platform.

The application serves as a mock server to demonstrate various workflows:
- Product reservation process
- Reservation status tracking
- Return to locker functionality

This example app is designed to help you understand the API integration points and test your implementation before connecting to the production environment.

## Features

- **Product Reservation**: Simulate the process of reserving products from a vending machine
- **Reservation Overview**: View and manage existing reservations
- **Return to Locker**: Create return codes for returning assets to lockers
- **Configuration Editor**: Easily configure API credentials and environment settings
- **Debug Console**: View API requests and responses when debug mode is enabled
- **Environment Selection**: Switch between different environments (Production, Staging, Test, Development, Local)

## Installation

### Option 1: Run with Docker (Recommended)

The project has been configured to use Docker with all required extensions enabled. To run the application with Docker:

1. Make sure you have Docker and Docker Compose installed on your system
2. Open a terminal in the project root directory
3. Run the following command:

```
docker-compose up -d
```

4. Access the application at http://localhost:8081

### Option 2: Run Directly with PHP

If you prefer to run the application directly without Docker, you need to ensure your PHP environment has the required extensions:

1. PHP 8.0 or higher
3. cURL extension enabled

##### Verifying SQLite3 is Enabled

To verify that PHP and Curl are properly enabled, you can create a simple PHP file with the following content:

```php
<?php
phpinfo();
```

Run this file and search for "sqlite" on the page. You should see a section for SQLite3 if it's properly enabled.

## Configuration

The application uses a `config.json` file to store configuration settings. You can edit this file directly or use the built-in Configuration Editor in the application.

### Configuration Options

- **API Key**: Your API key for authentication
- **Username**: Your username for API authentication
- **Password**: Your password for API authentication
- **Environment**: Select from Production, Staging, Test, Development, or Local
- **Debug Mode**: Enable/disable debug mode to view API requests and responses

### API Endpoints

The application uses the following API endpoints:

- `/api/external/machines` - Get available vending machines
- `/api/external/machines/stock/{machineId}` - Get stock for a specific machine
- `/api/external/stockreservations/update/true/true` - Create or update reservations
- `/api/external/stockreservations/stockreservationproducts` - Get reservation products
- `/api/external/pickupdeliveries/create/false` - Create pickup deliveries
- `/api/external/products/downloadthumbimage/{productId}` - Get product thumbnail images

## Usage

1. Start by accessing the main page of the application
2. Select one of the available functions:
   - Start product reservation
   - View reservation overview
   - Create return to locker code
   - Edit configuration
3. Follow the on-screen instructions for each function

## Project Structure

The project is organized into the following directories and files:

- `README.md` - This documentation file
- `docker-compose.yml` - Docker configuration for running the application
- `src/` - Main application code
  - `index.php` - Main entry point of the application
  - `conf/` - Configuration files and editor
    - `config-example.json` - Example configuration template
    - `config.json` - Application configuration file
    - `config_editor.php` - Configuration editor interface
  - `stockreservations/` - Files related to stock reservations
    - `fallback.png` - Default image for products without thumbnails
    - `image.php` - Handles product image display
    - `reservation_create.php` - Creates new reservations
    - `reservation_start.php` - Initiates the reservation process
    - `reservation_status.php` - Shows reservation status
    - `reservation_stock.php` - Manages stock for reservations
  - `pickupdeliveries/` - Files related to pickup and delivery processes
    - `pickupdeliveries_create.php` - Creates pickup delivery requests
    - `pickupdeliveries_overview.php` - Shows overview of pickup deliveries
    - `pickupdeliveries_start.php` - Initiates pickup delivery process
    - `pickupdelivieries_return.php` - Handles return to locker functionality

## Support and Contributing

### Getting Help

If you encounter any issues or have questions about using this example application:

1. Check the [Innovend Developer Documentation](https://www.postman.com/innovend/vendingweb-developer-portal-public/overview) for comprehensive guides and API references
2. Contact our support team at development@innovend.eu

### Contributing

We welcome contributions to improve this example application:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### License

This example application is provided under the MIT License. See the LICENSE file for details.
