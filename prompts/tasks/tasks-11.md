# Objective
## Middleware / Authentication Redirection Bug or Error

---

## Description
Whenever any users log in, the system sometimes shows a 403 "You do not have permission to access this resource" error. The authentication functionalities need to be double-checked, including verifying that login and logout work properly and that each kind of user is redirected to their proper portal. The database tables should also be checked in case there is a conflict causing the issue.

---

## Primary Objective
Identify and fix the cause of the intermittent 403 "You do not have permission to access this resource" error that occurs when users log in.

---

## Secondary Objectives
- Double check the authentication functionalities.
- Check if the logout and login work properly.
- Make sure it redirects each kind of user to their proper portal.
- Check the database tables for a possible conflict.

---

## Supporting Tasks

### Authentication & Login/Logout Verification
- Double check the authentication functionalities.
- Check if the logout works properly.
- Check if the login works properly.
- Make sure that it redirects to their proper portal for all kinds of users.

### Database Tables Check
- Check the database tables just for checking.
- Look for a possible conflict on the database tables.