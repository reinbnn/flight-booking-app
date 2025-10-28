# Database Migrations

This directory contains all database migration files for the Skybird Travel system.

## Files

- `001_create_initial_schema.sql` - Creates all database tables with proper relationships
- `002_seed_initial_data.sql` - Populates database with initial/sample data

## How to Run Migrations

### Using MySQL CLI

```bash
# Navigate to the migrations directory
cd database/migrations

# Option 1: Run both migrations
mysql -u root -p skybird_travel < 001_create_initial_schema.sql
mysql -u root -p skybird_travel < 002_seed_initial_data.sql

# Option 2: Create database and run migrations together
mysql -u root -p < 001_create_initial_schema.sql
mysql -u root -p < 002_seed_initial_data.sql





Using phpMyAdmin

1. Open phpMyAdmin in your browser
2. Click "Import" tab
3. Select 001_create_initial_schema.sql
4. Click "Go"
5. Repeat steps 2-4 for 002_seed_initial_data.sql

Using Database Management Tool

1. Open your database management tool (MySQL Workbench, DBeaver, etc.)
2. Open the migration file
3. Execute the SQL script