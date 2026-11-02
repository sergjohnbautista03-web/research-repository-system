# Journal workflow email notifications

The system queues an email after each successful transaction:

| Action | Recipients | Link |
| --- | --- | --- |
| Dean assigns a journal preparation task | The assigned active Research Coordinator | The assigned batch |
| Coordinator submits a completed batch | The active Dean who assigned the task | The batch awaiting review |
| Dean forwards the reviewed batch | Every active global Admin | The batch ready for publication |

Recipients use their registered email address. The sender is the application's Gmail account; recipients do not need to share Gmail passwords. Accounts must still have the appropriate role, department and assignment at delivery time. Opening a link requires sign-in and the normal batch access checks. Admin staff return to the requested batch after sign-in and policy acceptance.

Emails contain the task title, journal category, academic year, department, program, Dean, Coordinator, status, assignment date, event date, deadline and instructions. Submission and forwarding emails also list completed journal titles. Dates are displayed in Asia/Manila. A deadline can be supplied in the assignment form; existing tasks and assignments without a deadline display "Not specified" in email.

## Gmail configuration

The application already supports Gmail through Laravel SMTP. Configure the sender's credentials in the local `.env`; do not commit credentials:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-repository-sender@gmail.com
MAIL_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS=your-repository-sender@gmail.com
MAIL_FROM_NAME="UBE Research Repository"
MAIL_TIMEOUT=20
QUEUE_CONNECTION=database
APP_TIMEZONE=Asia/Manila
APP_URL=https://your-repository.example
```

Port 587 upgrades to TLS using STARTTLS. Google App Passwords require 2-Step Verification and may be unavailable under some organization/account policies. Use the sender's App Password in `MAIL_PASSWORD` when supported. Official instructions: [Google App Passwords](https://support.google.com/accounts/answer/185833) and [Google SMTP settings](https://support.google.com/mail/answer/7104828).

Set `APP_URL` to the actual address users can reach. Local testing can use `http://127.0.0.1:8000`; a localhost address works only on the computer running the repository. Production needs a reachable deployment URL. Email URLs generated from workflow requests point directly to that request's batch route.

`MAIL_MAILER=log` and `array` are for development/tests and do not deliver to Gmail. When the existing `failover` mailer is selected, workflow email uses its SMTP mailer directly so a failed delivery is retried instead of being silently logged as delivered.

## Database and worker

Apply the new deadline migration and any normal application migrations:

```powershell
php artisan migrate
php artisan config:clear
```

Keep a queue worker running, separately from `php artisan serve`:

```powershell
php artisan queue:work --tries=5 --timeout=60
```

The existing `composer dev` command also starts a queue listener. Use one of these worker setups; a web server alone does not process queued mail. Restart existing long-running workers after deploying the code/configuration:

```powershell
php artisan queue:restart
```

Each workflow email gets up to five attempts, with retry delays of 60, 300, 900 and 1800 seconds. The default database queue preserves pending jobs across web server restarts. SMTP errors leave the saved task or batch status intact. Unauthorized actions, invalid submissions, rolled-back transactions and repeated transitions do not enqueue new notifications. A genuine resubmission from preparation creates a new submission notification.

Inspect exhausted jobs, correct the delivery issue, and retry the relevant job UUID:

```powershell
php artisan queue:failed
php artisan queue:retry JOB_UUID
```

If dispatch itself fails (for example, the queue database is unavailable), Laravel reports that error in `storage/logs/laravel.log`. Missing or invalid recipient addresses are skipped; an event with no eligible recipients is logged. Do not use bulk retry commands without reviewing the pending/failed jobs.

## Verification

Automated tests use isolated databases and the in-memory mail transport. They cover the three stages, recipient scope, event details, deadlines, rendered HTML/text, authorization, rollback, repeated transitions, sign-in redirect and queue retry after delivery failure. They do not contact Gmail.

To verify live delivery, run the worker, assign one real task to a Coordinator, submit a complete batch, then forward it as the Dean. Check the recipient inboxes and each email's batch link. Review `queue:failed` and Laravel logs if mail does not arrive.
