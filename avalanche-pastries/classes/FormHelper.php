<?php
// Small helper so a form can collect ALL its errors instead of stopping at the first one.
class FormHelper
{
    // Runs $callback. If a setter rejects the value, its message is added to $errors.
    // Returns true when the value was accepted.
    public static function attempt(array &$errors, callable $callback): bool
    {
        try {
            $callback();
            return true;
        } catch (InvalidArgumentException | DomainException $e) {
            $errors[] = $e->getMessage();
            return false;
        }
    }

    // Turns a database error into a message for the page (instead of a blank page), and writes the
    // real reason to the PHP error log. While APP_DEBUG is true (config.php) the reason is shown too,
    // which makes a missing column or a broken table easy to find.
    public static function dbError(PDOException $e, string $message): string
    {
        error_log($message . ' ' . $e->getMessage());
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return $message . ' (' . $e->getMessage() . ')';
        }
        return $message . ' Please try again.';
    }
}
