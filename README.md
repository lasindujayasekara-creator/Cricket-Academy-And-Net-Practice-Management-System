# Cricket Academy & Net Practice Management System
**HND Final Year Project | PHP + MySQL + JavaScript**

---

## ✅ XAMPP Quick Start (3 Steps)

### Step 1: Start XAMPP
- Open **XAMPP Control Panel**
- Start **Apache** and **MySQL**

### Step 2: Import Database
- Open **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
- Click **"New"** → Create database named: `cricket_academy_db` → Click **Create**
- Select `cricket_academy_db` → Click **SQL tab**
- Open file: `c:\xampp\htdocs\cricketacademy\database\schema.sql`, copy all content and paste → Click **Go**

### Step 3: Open the App
👉 **[http://localhost/cricketacademy/](http://localhost/cricketacademy/)**

---

## 🔑 Login Credentials

| Role   | Username      | Password      |
|--------|--------------|---------------|
| Admin  | `admin`      | `password123` |
| Coach  | `coach_smith`| `password123` |
| Player | `player_john`| `password123` |

---

## 📁 Project Structure
```
cricketacademy/
├── config/db.php          # PDO MySQL connection
├── database/schema.sql    # Database + seed data
├── api/
│   ├── auth.php           # Login / Logout / Session
│   ├── dashboard.php      # Stats metrics per role
│   ├── players.php        # Player CRUD
│   ├── coaches.php        # Coach CRUD + schedules
│   ├── bookings.php       # Bookings + conflict check
│   ├── attendance.php     # Attendance sheets
│   └── payments.php       # Fee records + receipts
├── css/
│   ├── style.css          # Base variables & utilities
│   ├── auth.css           # Login portal styles
│   └── dashboard.css      # Dashboard layout & widgets
├── js/
│   ├── api.js             # Fetch API helper
│   ├── dashboard.js       # Full dashboard logic
│   └── charts.js          # Chart.js analytics
├── index.php              # Login page (entry point)
├── dashboard.php          # Protected dashboard workspace
└── report_print.php       # Printable PDF reports
```
