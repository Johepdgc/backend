# Symfony Project

## Overview

This project is a Symfony application designed to fetch data from an API daily and save it as a JSON file. It includes a Symfony console command that is scheduled to run daily using a cron job. The fetched data is processed and saved in a file named data_[YYYYMMDD].json.

## Tools and Technologies Used

- **Symfony Framework**: The main framework used to build the application.
- **PHP**: Programming language used for the application.
- **Composer**: Dependency manager for PHP packages.
- **PHPUnit**: Testing framework for writing unit tests.
- **SFTP**: Secure File Transfer Protocol for securely transferring files.
- **Cron**: Job scheduler used to run the Symfony command daily.

## Installation

### Prerequisites

- PHP 7.4 or higher
- Composer
- Symfony CLI (optional but recommended)
- PostgreSQL (or any other database supported by Doctrine)

1. **Clone the Repository**

   ```sh
   git clone https://github.com/Johepdgc/backend.git
   cd backend

2. **Clone the Repository**

   ```sh
   composer install

3. **Set Up Environment Variables**

   ```sh
   DATABASE_URL=postgresql://user:password@127.0.0.1:5432/database_name
   API_URL=https://api.example.com/data
   SFTP_HOST=sftp.example.com
   SFTP_USER=username
   SFTP_PASS=password

5. **Set Up the database**

   ```sh
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate

7. **Run the app**

   ```sh
   symfony server:start

9. **Running the command mannually**

    ```sh
    php bin/console app:fetch-data

11. **Setting the cron job**

    ```sh
    crontab -e
  Then add the job
  
    0 0 * * * /path/to/php /path/to/project/bin/console app:fetch-data >> /path/to/project/var/log/cron.log 2>&1

# Project Structure

- **src/Command/FetchDataCommand.php**: Symfony console command that fetches data from the API.
- **tests/Command/FetchDataCommandTest.php**: Unit tests for the FetchDataCommand.
- **.env**: Environment variables configuration file.
- **config/**: Symfony configuration files.
- **public/**: Publicly accessible files (e.g., index.php).
- **src/**: Application source code.
- **tests/**: Unit test files.
- **var/**: Cache and log files.
- **vendor/**: Composer dependencies.
