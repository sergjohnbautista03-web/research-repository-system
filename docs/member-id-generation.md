# Member ID formatting

The Create User page and Add User modal accept a numeric ID entered by the Dean:

- Student: enter one to eight digits. On save, pad the number on the left with zeros to eight characters. `37893` becomes `00037893`.
- Faculty: enter exactly six digits without a prefix. On save, add `FAC-`. `087987` becomes `FAC-087987`. Leading zeros supplied by the Dean are preserved.

The ID field is editable and uses numeric input. The existing username and password previews show the formatted ID while the Dean types. Validation rejects letters, symbols, empty IDs, Student numbers longer than eight digits, and Faculty numbers that do not contain exactly six digits. The server repeats validation and formatting independently of JavaScript. The `FAC-` prefix is added separately and is not counted toward the six input digits.

The form does not allocate sequential IDs or make reservation requests. Existing accounts keep their IDs. The form layout is unchanged.

Run `php artisan migrate` when deploying. The existing member ID migration retains the VARCHAR column, adds a unique index, and backfills ID history. Account creation uses a database transaction and a per-member-type row lock to check the final formatted ID. Consequently, Student inputs `37893` and `00037893` refer to the same ID and cannot create two accounts.

`member_identifiers` is permanent history with no cascading deletion. Imports and model-based account creation also record assigned IDs. Previously assigned IDs cannot be used again after the account is deleted. The existing history and sequence tables are retained; unused reservations from the earlier automatic-ID form do not block an otherwise unassigned manual ID. IDs permanently deleted before the original migration, with no historical record, cannot be recovered by the backfill.
