local_edztrainingcalendar

- Install by copying folder to moodle/local/ and visiting Site administration > Notifications.
- Upload CSV via /local/edztrainingcalendar/index.php (requires capability local/edztrainingcalendar:manage).
- CSV format: useremail,courseshortname,coursestartdate,expectedcmpldate,cohortidentifier
- Preview shows validation errors. Confirm to write to staging table.
- Scheduled task runs every 5 minutes and processes unprocessed rows.
- Add enrolment & cohort logic in classes/task/process_csv_task.php
