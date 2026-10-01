
# Timestamp convention

Application timestamps are written in Asia/Manila local time, configured in `config/db.php` through PHP's timezone and the PDO MySQL session (`+08:00`). The exception is `ai_suggestion_usage.last_generated_at`, stored in UTC because the cooldown compares it with `time()`; the AI quota day itself follows the Asia/Manila calendar day. MySQL `TIMESTAMP` columns are stored internally as UTC and converted according to the session timezone, while `DATETIME` columns retain literal values. Standalone scripts must load `config/db.php`; the installed `php.ini` default is `Europe/Berlin`.
