# PantryChef

PantryChef is a recipe and pantry management web application that helps users discover recipes based on available ingredients, manage pantry inventory, and view nutritional information.

The project also includes an admin panel for managing recipes, ingredients, and nutrition mappings.

---

# Why PantryChef Is Useful

PantryChef helps users:

* Discover recipes using available ingredients
* Organize pantry inventory
* Track nutritional recipe information
* Simplify meal preparation

---

# Features

## Main Website
* Browse recipes from the database
* Search recipes by name
* View detailed recipe pages
* Manage personal ingredient inventory
* Save favorite recipes
* Filter recipes by flavors and regions
* View nutritional recipe information
* Dark/light theme support

## Admin Panel
* Create recipes
* Modify existing recipes
* Manage ingredients
* Assign nutrition values to ingredients
* Manage recipe metadata

---

# Technologies Used

## Frontend
* HTML
* CSS
* JavaScript

## Backend
* PHP
* MariaDB

## Server
* Nginx
* PHP-FPM
* phpMyAdmin

---

# Project Structure

```text
/web        - Main PantryChef website
/admin      - PantryAdmin control panel
```

---

# Installation

## Requirements

Before installation, make sure your system has:

* PHP 8+
* MariaDB
* Nginx
* phpMyAdmin (optional)

---

## Clone the Repository

```bash
git clone https://github.com/Siegsaw/dysfunctionals.github.io.git
cd dysfunctionals.github.io
```

---

## Database Setup

Create the database:

```sql
CREATE DATABASE pantrychef;
```

Import the provided SQL schema into MariaDB.

---

## Configure Database Connection

Edit:

```text
/var/www/private/db.php
```

Set your database credentials.

---

## Configure Web Server

Set your web root to:

```text
/web
```

Admin panel:

```text
/admin
```

Make sure PHP-FPM is enabled.

---

## Run the Project

Start Nginx and MariaDB.

Main website:

```text
http://your-server/web
```

Admin panel:

```text
http://your-server/admin
```

---

# Documentation

Detailed project documentation is available in the wiki:

https://github.com/Siegsaw/dysfunctionals.github.io/wiki

The wiki contains:

* Main website page documentation
* Admin page documentation
* Functionality descriptions
* Interface screenshots
* User interaction guides

---

# Getting Help

For help:

* Check the project wiki
* Open an issue on GitHub

---

# Contributors

Project developed by the Dysfunctionals team.

**Project Lead** - https://github.com/Siegsaw

https://github.com/Viktorija002  

https://github.com/CaptainUnderpants-67
