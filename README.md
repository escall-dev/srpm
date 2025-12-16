# 🏠 Smart Rental Property Management

### A System for Lease Tracking and Automated Rent Collection

---

## 📘 Overview

**Smart Rental Property Management** (codename: **Leaseman**) is a Laravel-based web system designed to simplify property leasing and rent collection.  
It enables **owners** to manage multiple properties, track leases, monitor rent payments, and handle maintenance requests — while **tenants** can view their lease details, pay rent online, and submit maintenance requests through a self-service portal.

This system provides a **centralized platform** for managing buildings, tenants, leases, payments, maintenance requests, and notifications — all in one intuitive dashboard.

---

## ⚙️ Core Features

| Module                          | Description                                                  |
| ------------------------------- | ------------------------------------------------------------ |
| **Lease Tracking**              | Manage lease durations, start/end dates, and renewals.       |
| **Automated Rent Collection**   | Track paid/unpaid rents with digital receipts.               |
| **Penalty Management**          | Auto-calculate late fees and notify tenants.                 |
| **Maintenance Requests**        | Tenants can request repairs; owners track progress.          |
| **Digital Document Management** | Upload & access lease contracts, IDs, and receipts securely. |
| **Analytics Dashboard**         | View monthly/annual income, expenses, and occupancy reports. |
| **Notifications & Alerts**      | Auto reminders for rent due dates, renewals, and updates.    |

---

## 🛠️ Tech Stack

| Layer              | Technology                                  |
| ------------------ | ------------------------------------------- |
| **Framework**      | Laravel 12                                  |
| **Database**       | MySQL                                       |
| **Frontend**       | Blade / Livewire / Alpine.js                |
| **CSS Framework**  | Tailwind CSS v4                             |
| **Authentication** | Laravel Breeze / Fortify                    |
| **Charts**         | Chart.js or ApexCharts                      |
| **Payments**       | GCash API / E-Wallet Integration            |
| **Notifications**  | Laravel Notifications (Mail, Database, SMS) |

---

# 🚀 Laravel Project Installation Guide

This guide will help you **clone**, **install**, and **run** this
Laravel project on your local machine.

---

## 📌 **Requirements**

Before starting, make sure your system has the following installed:

### **Server Requirements**

-   **PHP 8.2+**
-   **Composer 2.x**
-   **MySQL 8 / MariaDB 10+**
-   **Node.js 18+**
-   **NPM 9+** or **PNPM / Yarn** (optional)
-   **Laravel CLI** (optional)
-   **Git**

### **PHP Extensions Required**

Laravel requires the following PHP extensions:

-   OpenSSL\
-   PDO\
-   Mbstring\
-   Tokenizer\
-   XML\
-   Ctype\
-   JSON\
-   BCMath\
-   Fileinfo\
-   GD (for image processing)

---

## 📥 **1. Clone the Repository**

```bash
git clone https://github.com/Ahadon13/SRPM-Client.git
```

Go inside the project:

```bash
cd your-repo
```

---

## 📦 **2. Install PHP Dependencies**

```bash
composer install
```

---

## 📁 **3. Create Environment File**

```bash
cp .env.example .env
php artisan key:generate
php artisan storage:link
```

---

## 🗄️ **4. Configure Database**

Update `.env`:

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=srpm_db
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

Create the database:

```sql
CREATE DATABASE your_database;
```

---

## 🧱 **5. Run Migrations & Seeders (if available)**

```bash
php artisan migrate
```

If you want a fake data, you can (Note: Not Accurate Data);

```bash
php artisan migrate:fresh --seed
```

---

## 📦 **6. Install Frontend Dependencies**

```bash
npm install
```

---

## ▶️ **7. Start the Laravel Server**

Open your git bash terminal on your project and run this;

```bash
./dev.sh
```

Your app will run at:

    http://127.0.0.1:8000

---

## 🎉 Done!

Your Laravel project is now successfully installed and running.
