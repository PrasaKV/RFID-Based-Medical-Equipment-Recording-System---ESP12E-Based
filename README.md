# RFID-Based Medical Equipment Recording System

A PHP/MySQL medical-equipment management system with ESP8266 RFID scanning. The system records equipment, locations, preventive maintenance, issues, repairs, replacement parts, and RFID scan activity through a browser dashboard and JSON API.

## Features

- Dashboard with equipment totals, live scan history, upcoming preventive maintenance, and open issues
- Equipment inventory with search and filters for category, status, and location
- RFID tag registration and scanning
- Equipment detail pages with location, maintenance, issue, repair, and replacement-part history
- Preventive maintenance scheduling and automatic `PM_DUE` flagging when maintenance is due within seven days
- Issue reporting and resolution tracking
- Repair intake and machine return/delivery records
- Replacement-part records linked to equipment, repairs, or issue tickets
- Admin management for users, roles, departments, and categories
- Standalone live RFID event dashboard at `System/index.html`
- ESP8266/ESP-12E firmware using an MFRC522 RFID reader and 16x2 I2C LCD

## Technology

- PHP 8 or later
- MySQL or MariaDB
- Apache or another PHP-capable web server
- PDO MySQL extension
- ESP8266/ESP-12E Arduino environment for the hardware client
- Browser-side Tailwind CSS and Google Fonts loaded from CDNs

## Project Structure

```text
.
├── ESP12E Programme/
│   └── Programme.ino             ESP8266 RFID reader firmware
├── System/
│   ├── dashboard.php             Authenticated operations dashboard
│   ├── device.php                Equipment detail and history view
│   ├── inventory.php             Equipment registry
│   ├── login.php                 Login page
│   ├── repairs.php               Repair and parts workspace
│   ├── index.html                Live RFID event monitor
│   ├── admin/                    User and department administration pages
│   ├── api/                      JSON endpoints grouped by resource
│   ├── data/rfid_events.json     File-backed RFID event log, max 200 events
│   ├── includes/                 Authentication, database, header, and footer helpers
│   └── setup/                    Database schema, setup page, and migrations
└── README.md
```

## Local Installation

1. Install PHP with PDO MySQL, MySQL/MariaDB, and Apache.
2. Configure Apache so `System/` is the web document root, or place the contents of `System/` in the server's document root. The application uses root-relative URLs such as `/login.php` and `/api/rfid/scan.php`.
3. Ensure PHP can write to `System/data/rfid_events.json` and the `System/data/` directory.
4. Start MySQL/MariaDB with a local `root` account and no password, or update the connection settings in `System/includes/db.php` and `System/setup/init.php`.
5. Open the setup page in a browser:

   ```text
   http://localhost/setup/init.php
   ```

6. Click **Run Setup SQL**. This creates the `rfid-system` database, tables, roles, and initial users.
7. To populate the system with realistic hospital medical equipment, RFID movement history, maintenance schedules, repair logs, and issue tickets, run the seeder:

   - **From Browser**: open `http://localhost/setup/seed.php` and click **Seed Database**
   - **From Terminal (CLI)**:
     ```bash
     php System/setup/seed.php
     # Or to wipe and re-seed from scratch:
     php System/setup/seed.php --fresh
     ```
   *(Note: `System/includes/db.php` will also automatically trigger the seeder on initial connection if tables are empty).*

8. Sign in at:

   ```text
   http://localhost/login.php
   ```

9. After setup, restrict or remove access to `System/setup/`.

### Alternative database setup

The schema can also be applied from the MySQL client:

```bash
mysql -u root -p < System/setup/setup.sql
```

If the database connection succeeds, `System/includes/db.php` automatically runs the migration checks in `System/setup/migrate.php` and auto-seeds initial data.

## Default Accounts

The setup SQL currently creates these accounts:

| Username    | Email                     | Password   | Role       |
| ----------- | ------------------------- | ---------- | ---------- |
| `admin`     | `admin@hospital.local`    | `admin123` | `admin`    |
| `biomedeng` | `engineer@hospital.local` | `eng123`   | `engineer` |

Change or replace these credentials before using the system outside a local development environment.

## Roles and Permissions

- **Admin**: full access, user management, and deletion of records
- **Engineer**: equipment and maintenance editing, issue and repair workflows
- **Technician**: authenticated scanning and inspection workflows; cannot delete records

The shared authorization helpers are in `System/includes/auth.php`. Some older JSON endpoints are intentionally lightweight and should be reviewed before exposing the API to an untrusted network.

## RFID API

The ESP8266 and other readers send JSON to:

```text
POST /api/rfid/scan.php
Content-Type: application/json
```

Example scan payload:

```json
{
	"mode": "scan",
	"tag_id": "TAG-001",
	"location": "ICU",
	"reader_id": "READER-ICU-01"
}
```

Example registration payload:

```json
{
	"mode": "register",
	"tag_id": "TAG-NEW-001",
	"location": "ICU",
	"reader_id": "READER-ICU-01"
}
```

The scan endpoint:

- accepts JSON or form data
- stores the event in `System/data/rfid_events.json`
- keeps the newest 200 events
- matches `tag_id` against `equipment.rfid_tag_id` when MySQL is available
- automatically tags and updates `equipment.current_location` with the reader's sent location
- records a chronological movement trail in `location_logs`
- changes operational equipment to `PM_DUE` when its next maintenance date is within seven days

Useful RFID endpoints:

| Endpoint                   | Purpose                                           |
| -------------------------- | ------------------------------------------------- |
| `GET /api/rfid/events.php` | Return stored events for the live dashboard       |
| `GET /api/rfid/poll.php`   | Return recent events enriched with equipment data |
| `POST /api/rfid/scan.php`  | Receive a reader event                            |
| `POST /api/rfid/clear.php` | Clear the file-backed event log                   |

## ESP8266 Setup

Open `ESP12E Programme/Programme.ino` in the Arduino IDE or PlatformIO and install these libraries:

- ESP8266 board support package
- `LiquidCrystal_I2C`
- `MFRC522`

Before flashing, update the Wi-Fi credentials and the server URL in `postRequest()`:

```cpp
const char* ssid = "your-wifi-name";
const char* password = "your-wifi-password";

http.begin(client, "http://YOUR_SERVER_IP:8080/api/rfid/scan.php");
```

The firmware supports two modes selected with the toggle input:

- **Scan**: sends `{ "mode": "scan", "tag_id": "..." }`
- **Register**: sends `{ "mode": "register", "tag_id": "..." }`

Current pin mapping:

| Component      | ESP8266 pin |
| -------------- | ----------- |
| Buzzer         | `D0`        |
| Mode toggle    | `D8`        |
| I2C SDA        | `D3`        |
| I2C SCL        | `D4`        |
| MFRC522 RST    | `D1`        |
| MFRC522 SS/SDA | `D2`        |

The LCD is configured for I2C address `0x27` and dimensions `16x2`.

## Other API Groups

The JSON API is organized under `System/api/`:

- `equipment/`: create, list, get, update, and delete equipment
- `maintenance/`: create and list maintenance logs
- `issues/`: create, list, get, update, and delete issue tickets
- `repairs/`: intake, list, get, update, return, and delete repair records
- `parts/`: create, list, update, and delete replacement-part records
- `users/`: user administration endpoints
- `roles/`: role management endpoints
- `departments/` and `categories/`: master-data management
- `location/`: location lookup

Most browser workflows use authenticated sessions. API responses are JSON and generally accept either JSON request bodies or form data.

## Security Notes

This repository is configured for a local development setup and should not be deployed unchanged to the public internet:

- Passwords are currently stored and compared as plain text. Use `password_hash()` and `password_verify()` before production use.
- Database credentials are hard-coded as `root` with an empty password by default. Use a dedicated database user and environment-based configuration.
- The ESP8266 firmware contains Wi-Fi credentials and an HTTP endpoint. Replace these before sharing or flashing the firmware.
- Several endpoints allow cross-origin requests for local IoT connectivity. Restrict CORS and add device authentication for a production deployment.
- Protect `setup/`, `data/`, and administrative endpoints from direct public access as appropriate for the server configuration.
- Add HTTPS, CSRF protection for browser mutations, request validation, rate limiting, and audit logging before handling real clinical or personally identifiable data.

## Troubleshooting

- **Database connection failed**: verify MySQL is running, the database is named `rfid-system`, PDO MySQL is enabled, and the credentials match `System/includes/db.php`.
- **Setup page cannot create tables**: verify the MySQL account can create databases and tables.
- **RFID events do not appear**: verify Apache is reachable from the ESP8266, the endpoint IP and port are correct, and `System/data/rfid_events.json` is writable.
- **A tag scan is not linked to equipment**: the incoming tag ID must exactly match `equipment.rfid_tag_id`.
- **The application redirects incorrectly**: serve `System/` as the web root because the PHP pages use root-relative paths.
