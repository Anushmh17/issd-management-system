# ISSD Management System — Admin Panel User Manual

> **Institute of Software Skills Development**
> Version 1.0 · Last Updated: May 2026

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Dashboard](#2-dashboard)
3. [Student Management](#3-student-management)
4. [Enrollment Management](#4-enrollment-management)
5. [Course Management](#5-course-management)
6. [Lecturer Management](#6-lecturer-management)
7. [Lecturer Payroll](#7-lecturer-payroll)
8. [Student Payments](#8-student-payments)
9. [Finance Hub](#9-finance-hub)
10. [Leads & Inquiries](#10-leads--inquiries)
11. [Certificates & Completions](#11-certificates--completions)
12. [Documents](#12-documents)
13. [Notices](#13-notices)
14. [Calendar](#14-calendar)
15. [Notifications](#15-notifications)
16. [Reports & Analytics](#16-reports--analytics)
17. [Account Settings](#17-account-settings)

---

## 1. Getting Started

### Login
- Navigate to `http://localhost/issd_management/`
- Enter your **Admin username/email** and **password**
- Click **Log In**

### Admin Roles
| Role | Access Level |
|---|---|
| `admin` | Full access to all modules |
| `lecturer` | Dashboard, own courses, assignments, notices |
| `student` | Own profile, payments, courses, notices |

### Navigation
The **left sidebar** contains all module links. The **top bar** shows:
- 🔔 **Notifications bell** — real-time payment alerts and system updates
- **User avatar** → Profile / Settings / Logout

---

## 2. Dashboard

**Path:** `frontend/admin/dashboard.php`

The admin dashboard provides a real-time overview of the institution:

### Stat Cards (top row)
| Card | What it shows | Click action |
|---|---|---|
| Total Students | All registered students | — |
| Active Learners | Students with ongoing courses | — |
| Pending Payments | Students with outstanding balances | Links to Payment Alerts |
| Monthly Revenue | Revenue collected this month | Links to Finance Hub |

### Dashboard Sections
- **Recent Activity** — Latest enrollments, payments, and lecturer additions
- **Missing Documents** — Students with incomplete document submissions
- **Quick Actions** — Shortcuts to add student, record payment, post notice

> **Tip:** All stat cards update in real time on every page load.

---

## 3. Student Management

**Path:** `admin/students/`

### View All Students
- Go to **Students** in the sidebar
- Use the **Search bar** to filter by name, email, phone, or student ID
- Filter by **Status** (Active / Inactive)

### Add a Student
1. Click **+ Add Student**
2. Fill in: Full Name, Email, Phone, NIC, Address, Date of Birth
3. Upload a profile photo (optional)
4. Set status to **Active**
5. Click **Save Student**

### Edit a Student
1. Find the student in the list → click the **✏️ Edit** button
2. Update any fields
3. Click **Update Student**

### Student Profile
Clicking a student's name opens their full profile showing:
- Personal details
- Enrolled courses
- Payment history
- Uploaded documents

### Delete a Student
Click the **🗑️ Delete** button → confirm the prompt.
> ⚠️ Deletion is permanent and removes all related records.

---

## 4. Enrollment Management

**Path:** `frontend/admin/enrollments.php`

### View Enrollments
Displays all student–course assignments with status badges:
- 🟢 **Ongoing** — Currently enrolled
- ✅ **Completed** — Course finished
- 🔴 **Dropped** — Enrollment cancelled

### Create a New Enrollment
1. Click **+ New Enrollment**
2. Select **Student** and **Course**
3. Set **Start Date** (End Date optional)
4. Click **Enroll**

### Update Enrollment Status
Use the status dropdown next to each enrollment row to change it between Ongoing / Completed / Dropped.

---

## 5. Course Management

**Path:** `admin/courses/`

### View Courses
Lists all courses with name, code, monthly fee, student count, and assigned lecturer.

### Add a Course
1. Click **+ Add Course**
2. Fill in: Course Name, Course Code, Duration (months), Monthly Fee, Description
3. Set **Status** (Active / Inactive)
4. Click **Save**

### Edit / Delete a Course
Use the **Edit** or **Delete** action buttons in the course list.

### Assign a Lecturer to a Course
1. Click **Assign Lecturer** on any course card
2. Select the lecturer from the dropdown
3. Click **Assign**

### Assign a Student to a Course
1. Click **Assign Student** on any course card
2. Select the student
3. Click **Assign**

---

## 6. Lecturer Management

**Path:** `admin/lecturers/`

### View Lecturers
Displays all academic staff with name, department, email, employee ID, and status.

### Add a Lecturer
1. Click **+ Add Lecturer**
2. Fill in: Full Name, Email, Phone, Username, Password, Department, Employee ID, Qualifications, Joined Date
3. Upload a profile photo (optional)
4. Set **Status** (Active / Inactive)
5. Click **Save**

### Edit / Delete a Lecturer
Use the **Edit** or **Delete** buttons in the lecturer list.

> Lecturers can log in with their own credentials to access their dashboard, courses, assignments, and notices.

---

## 7. Lecturer Payroll

**Path:** `admin/lecturer_payments/`

### Payroll Hub
The hub shows three live stat cards:
| Card | Description |
|---|---|
| Total Staff Payouts | Total amount paid out this month |
| Active Lecturers | Number of registered active lecturers |
| Payroll Status | Completion status — click to see who is unpaid |

### Payroll Status Card (Clickable)
Clicking the **Payroll Status** card opens a modal showing:
- Progress bar (% of lecturers paid this month)
- List of **unpaid lecturers** with name and department
- **Pay Now** button per lecturer → takes you directly to the payout form

### Record a Payout
1. Click **+ Record Payout** (or "Pay Now" in the modal)
2. **Select Lecturer** — dropdown auto-loads their assigned courses
3. **Select Course** (optional) — shows enrolled student count
4. **Choose Payment Mode:**
   - **Flat Monthly** — Enter a fixed amount (pre-filled from course fee)
   - **Per Student** — Enter rate per student × student count → total auto-calculated
5. Set the **Month** (defaults to current month)
6. Add **Notes** (optional)
7. Click **Confirm & Record Payout**

### Payout History
The table below the stat cards shows all recorded payouts with date, lecturer, month, amount, and status.

### Automatic Payment Alerts
From the **20th of each month**, the notification system automatically creates alerts for any lecturer who hasn't been paid. These appear in the **Payments** tab of the notification bell and are auto-cleared once the payout is recorded.

---

## 8. Student Payments

**Path:** `admin/payments/`

### View Payments
Lists all student payment records with student name, course, month, amount paid, balance, and status.

### Record a Payment
1. Click **+ Add Payment**
2. Select **Student** and **Course**
3. The system auto-loads the **monthly fee** and any **outstanding balance**
4. Enter amount paid and payment method
5. Add reference/notes if needed
6. Click **Record Payment**

### Payment Details
Click any payment row to view the full breakdown:
- Previous balance carried forward
- Monthly fee
- Amount paid
- Remaining balance
- Next due date

### Payment Alerts
**Path:** `admin/payments/alerts.php`

Shows three alert categories:
- 🔴 **Overdue** — Past the due date with outstanding balance
- 🟡 **Due Soon** — Due within the next 7 days
- 🟠 **High Balance** — Balance exceeds a threshold

Alerts are auto-generated and refresh every 5 seconds via the notification system.

---

## 9. Finance Hub

**Path:** `admin/finance/`

Provides a consolidated financial overview:
- **Monthly Revenue** — Total student payments this month
- **Monthly Expenses** — Total lecturer payouts this month
- **Net Income** — Revenue minus expenses
- **Revenue vs Expense** chart
- **Recent Transactions** — Combined student + lecturer payment feed

Quick action buttons:
- **+ New Student Payment**
- **Pay Lecturer**

---

## 10. Leads & Inquiries

**Path:** `admin/leads/`

Manages prospective student inquiries:

### View Leads
Table of all leads with name, contact, course of interest, status, and follow-up date.

### Add a Lead
1. Click **+ Add Lead**
2. Enter: Name, Phone, Email, Course Interested In, Notes
3. Set **Status** (New / Contacted / Enrolled / Lost)
4. Set a **Follow-up Date**

### Edit / Delete
Use action buttons in the leads table.

> Leads with overdue follow-up dates trigger **Call Follow-up** alerts in the notification bell.

---

## 11. Certificates & Completions

**Path:** `admin/certificates/`

### View Certificates
Lists all issued certificates with student name, course, completion date, and certificate ID.

### Issue a Certificate
1. Click **+ Issue Certificate**
2. Select the **Student** and **Course**
3. Enter the **Completion Date**
4. Click **Issue**

> Issuing a certificate automatically marks the student's enrollment as **Completed**.

---

## 12. Documents

**Path:** `admin/documents/`

### View Document Status
Shows all students with their uploaded document status:
- ✅ Uploaded
- ❌ Missing

### Required Documents (per student)
- NIC (Front)
- NIC (Back)
- Birth Certificate
- Passport Photo
- Address Proof

### Manage Documents
**Path:** `admin/documents/manage.php`
- Upload documents on behalf of a student
- Delete incorrect uploads
- View uploaded files

> Students with missing documents appear in the **Missing Documents** widget on the dashboard.

---

## 13. Notices

**Path:** `frontend/admin/notices.php`

### View All Notices
Lists all posted notices with title, content preview, target audience, posted by, and date.

### Post a Notice
1. Click **+ Post Notice**
2. Enter **Title** and **Content**
3. Choose **Target Audience:**
   - All (Everyone)
   - Students Only
   - Lecturers Only
   - Admins Only
4. Click **Publish Notice**

### Edit / Delete a Notice
Use the **✏️ Edit** or **🗑️ Delete** action buttons in the notice list.

> Notices are immediately visible to the targeted audience on their respective dashboards.

---

## 14. Calendar

**Path:** `admin/calendar.php`

- Visual monthly calendar showing scheduled events
- Add events by clicking a date
- Events are categorized by type (class, payment due, meeting, etc.)

---

## 15. Notifications

The notification bell (🔔) in the top bar shows real-time alerts. It refreshes every **5 seconds** automatically.

### Notification Categories
| Tab | Types of alerts |
|---|---|
| All | Everything |
| Calls | Overdue lead follow-ups |
| Payments | Student payment overdue/due soon, lecturer payout due |
| System | General system alerts |

### Actions
- Click a notification to mark it as read and navigate to the relevant page
- **Mark All Read** — clears the unread count
- **View History** — shows previously cleared notifications

### Auto-Generated Alerts
| Trigger | Alert type |
|---|---|
| Student payment overdue | Payment — Overdue |
| Student payment due in 7 days | Payment — Due Soon |
| Lecturer unpaid from day 20+ | Payment — Lecturer Payout Due |
| Lead follow-up date passed | Call — Follow-up Required |

---

## 16. Reports & Analytics

**Path:** `frontend/admin/reports.php`

Printable A4-ready report including:
- Total Students, Active Learners, Courses, Revenue
- Month-over-month growth
- Revenue vs Expense breakdown
- Top performing courses
- Formal approval/signature section

### Print a Report
Click the **🖨️ Print** button — the page switches to print-optimized layout automatically.

---

## 17. Account Settings

### My Profile
**Path:** `frontend/shared_profile.php`
- Update your name, email, phone
- Change your profile photo
- Update your password

### Settings
**Path:** `frontend/shared_settings.php`
- Manage notification preferences
- System configuration options

### Logout
Click your **avatar → Logout** in the top-right corner, or use the **Logout** link at the bottom of the sidebar.

---

## Quick Reference — Common Tasks

| Task | Steps |
|---|---|
| Add a new student | Students → + Add Student |
| Enroll student in a course | Enrollments → + New Enrollment |
| Record a student payment | Payments → + Add Payment |
| Pay a lecturer | Lecturer Pays → + Record Payout OR click Payroll Status card |
| Post a notice | Notices → + Post Notice |
| Issue a certificate | Certificates → + Issue Certificate |
| View overdue payments | Payments → Alerts OR check Notifications bell |
| Check unpaid lecturers | Lecturer Pays → click Payroll Status card |
| Generate a report | Reports (sidebar) → Print |

---

## Technical Notes

- **Stack:** PHP 8+, MySQL (via PDO), Bootstrap 5, vanilla CSS
- **Local URL:** `http://localhost/issd_management/`
- **Database:** `issd_management` (MySQL via XAMPP)
- **Session timeout:** Based on PHP session config
- **CSS cache:** Busted on every load via `?v=timestamp`

---

*© 2026 ISSD — Institute of Software Skills Development. All rights reserved.*
