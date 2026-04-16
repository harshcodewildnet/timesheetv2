Modules:

1. Admin
2. Login/Registration
3. Task
4. Report
5. Email/Alerts/Notification

Entity Hierarchy:
Super Admin
-HR:
-HOD:
-RM: team members details
-Executive

Graphs:
gross work hours
leaves
work days

Executive:
-add/view/edit tasks
RMs:
-same as Exec
-approve/disapprove team tasks
HODs:
-view dept stats
-approve/disapprove RMs
HR:
-view all dept stats
-assign roles/RMs
-add/manage executive -

Tasks:
-add employee(by admin/hr)
-manage employee(admin/hr)
-edit entries
-manage profile

add apporval status filter -

HR->admin - create all users -> assign dept, rm

admin,hod - no timesheet

dept select all check not working on dash-admin pg
week work hours chart data

if dept/subdept change of rm/executive then change their rm_id

graphs
csv import
pagination
email triggers

escalation entries repeating for same emp if multiple tasks are pending
add asterisk in forms

triggers and time interval management of unapproved tasks
(optional) escalation of missed timesheet cases to Dep Head
password hash
modify php
session time duration
timeout for otp in passowrd change
admin logs in thru otp
timeout for otp in admin
image inspect/download restriction
... in edit description
'no entries' tr not applied everywhere
empid editable in edit modal only after add modal opened
field errors in admin forms not clearing on close modal
delete task if done without page reload, 'no-task entry' doesn't come
review modals clearing and errors display
give manager filter on dashboard hod


task categories not coming for users with dept_id 4 and subdeptid not null/0

password hashing
db backup
table archival
workdays graph
scoll add task table
sort filters alphabetically - done
if ($result->num_rows >= 1) to if ($result->num_rows === 1)(changed to add Bhumi's id to Waseem's id)
add spinner on applyFilters() - done
check rm filter -> emp filter, graphs on dashboard-hod
check emp filter on clear filter btn in dash-admin
task entry for 26-05-1993

laravel structure
sql - indexing, partition - done
load balancing
laravel demo project


password change on admin portal when saving any form

relieve Arjun Sareen - user deleted from timesheet

task date flaw bug may be due to month end case
also check other option for same (subcategory id -2 is saved in db) 

mailer error 
client module -add/edit/de-activate
task inline edit cat and brief not prefilled

