🧾 Invoice Management via Excel Import – Laravel RPC API
📘 Project Overview

This project is a PHP Laravel RPC API designed for managing invoices, featuring the ability to import invoices from Excel files.
The API allows admin users to perform full CRUD operations (Create, Read, Update, Delete) on invoices, clients, and related entities.
The system uses PostgreSQL as the database with code-first migrations to define and manage the schema.

⚙️ Core Features
🧩 Functions

Create an Import – Upload and process invoices from an Excel file.

Update – Modify existing invoice records.

Delete – Remove specific invoice records.

View All Imports (Paginated) – Retrieve a paginated list of all imported invoices.

👥 User Roles
🔐 Admin

Upload (Insert) Imports

Update Invoices

Delete Invoices

🌐 Public User

Read (View) Invoices

🗄️ Database & Schema Design

Approach: Code-first migrations

Database: PostgreSQL

Entities include: clients, invoices, and invoice_items

✅ Validations

All required fields for clients, invoices, and items must be validated.

Validate Excel file format and data integrity before processing.

Provide clear validation error messages.

⚠️ Error Handling

Return meaningful error messages for:

Invalid requests

Validation failures

Authentication errors

Ensure secure and consistent API responses.

🧪 Testing

Implement Unit Tests and Feature Tests for critical functionalities.

Test all CRUD operations, Excel import validation, and role-based access.

🧰 Technical Requirements

Framework: Laravel (latest stable version)

Database: PostgreSQL

Architecture: Service Interface pattern

Security: JWT or Laravel Sanctum for API authentication

📚 API Documentation

Provide a comprehensive API documentation including:

Endpoint descriptions

Request/Response formats (with examples)

Error codes and messages

A Postman Collection must be included to demonstrate and test all endpoints.

⏱️ Cron Job

A scheduled Cron Job should run every 5 minutes to:

Automatically send created invoices to the Tax Authorities API.

Fiscalization API Reference:
Tax API Documentation

🎥 Task Recording

All work must be recorded using FlashBack Recorder.

📥 Download FlashBack Recorder

🔑 License Key: XBP9-GBCT-GFD9-4REZ

Note:

Send daily recordings via WeTransfer during task progress.

Final review will be based on the completion of all steps.

📝 Evaluation Criteria
Criteria Description
💻 Code Quality Clean, maintainable, and well-documented code
🧱 Laravel Features Proper usage of Eloquent, Migrations, Validation, Middleware, etc.
🔒 Security Secure endpoints, authentication, and validation
🧪 Testing Comprehensive coverage with unit and feature tests
📄 Documentation Clear, complete API documentation and Postman collection
✅ Deliverables


