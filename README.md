# Inventory-Management-System-

Inventory Management System for Ceyntics Systems (Pvt) Ltd

## Project Structure

- backend: Laravel API for authentication, inventory, borrow/return, and audit logs.
- frontend: Next.js operator console for all required management screens.
- docs: Submission-ready implementation and architecture documentation.

## Quick Start

### Backend

1. cd backend
2. composer install
3. copy .env.example .env
4. php artisan key:generate
5. php artisan migrate --seed
6. php artisan serve

API base URL: http://127.0.0.1:8000/api/v1

### Frontend

1. cd frontend
2. npm install
3. set NEXT_PUBLIC_API_BASE_URL in .env.local (optional, defaults to http://127.0.0.1:8000/api/v1)
4. npm run dev

Frontend URL: http://localhost:3000

## Step 12 Deliverables

- Final implementation and submission guide: docs/FINAL_SUBMISSION.md
- Audit API endpoint: GET /api/v1/activity-logs
- Required UI pages are available under frontend/src/app

## Testing

Backend:

- php artisan test

Frontend:

- npm run lint
