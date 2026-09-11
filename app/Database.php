<?php
/**
 * app/Database.php
 *
 * Connection strategy mirroring the working billing app:
 *   1. sqlsrv + explicit "ODBC Driver 18 for SQL Server"
 *   2. sqlsrv + auto-detect driver
 *   3. odbc + Driver 18
 *   4. odbc + Driver 17
 *
 * On failure, dumps each attempt's error so you can see exactly
 * which driver is misbehaving on this machine.
 */
class Database {
    private static ?PDO $pdo = null;

    // ═══════════════════════════════════════════════════════════
    //  EDIT THESE PER ENVIRONMENT
    // ═══════════════════════════════════════════════════════════
    //
    //  DEV (XAMPP on your local PC):
    //      $server   = 'localhost\SQLEXPRESS';
    //      $database = 'AmnDb006';
    //
    //  PRODUCTION (Windows Server + SQL Server Enterprise,
    //              same machine as the working billing app):
    //      $server   = 'localhost';
    //      $database = 'AmnDb006';
    //
    //  If your tracker uses a different DB name on prod, change it.
    // ═══════════════════════════════════════════════════════════
    private const SERVER   = 'localhost\\SQLEXPRESS';        // ← change to 'localhost\SQLEXPRESS' for local XAMPP
    private const DATABASE = 'AmnDb006';         // ← tracker database name
    private const USER     = 'sa';
    private const PASS     = 'P@ssw0rd';         // ← same password as the billing app
    // ═══════════════════════════════════════════════════════════

    public static function connection(): PDO {
        if (self::$pdo !== null) return self::$pdo;

        $errors = [];
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        // ── Attempt 1: sqlsrv + Driver 18 explicitly ──────────────
        if (in_array('sqlsrv', PDO::getAvailableDrivers())) {
            try {
                $dsn = "sqlsrv:Server=" . self::SERVER . ";Database=" . self::DATABASE
                     . ";Driver=ODBC Driver 18 for SQL Server"
                     . ";TrustServerCertificate=yes;Encrypt=no";
                self::$pdo = new PDO($dsn, self::USER, self::PASS, $opts + [
                    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                ]);
                return self::$pdo;
            } catch (PDOException $e) {
                $errors[] = "sqlsrv(Driver18): " . $e->getMessage();
            }

            // ── Attempt 2: sqlsrv auto-detect ─────────────────────
            try {
                $dsn = "sqlsrv:Server=" . self::SERVER . ";Database=" . self::DATABASE
                     . ";TrustServerCertificate=yes;Encrypt=no";
                self::$pdo = new PDO($dsn, self::USER, self::PASS, $opts + [
                    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                ]);
                return self::$pdo;
            } catch (PDOException $e) {
                $errors[] = "sqlsrv(auto): " . $e->getMessage();
            }
        } else {
            $errors[] = "sqlsrv: pdo_sqlsrv extension NOT loaded in this PHP";
        }

        // ── Attempt 3: ODBC + Driver 18 ───────────────────────────
        try {
            $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};Server=" . self::SERVER
                 . ";Database=" . self::DATABASE
                 . ";TrustServerCertificate=yes;Encrypt=no;";
            self::$pdo = new PDO($dsn, self::USER, self::PASS, $opts);
            return self::$pdo;
        } catch (PDOException $e) {
            $errors[] = "odbc(Driver18): " . $e->getMessage();
        }

        // ── Attempt 4: ODBC + Driver 17 ───────────────────────────
        try {
            $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=" . self::SERVER
                 . ";Database=" . self::DATABASE
                 . ";TrustServerCertificate=yes;";
            self::$pdo = new PDO($dsn, self::USER, self::PASS, $opts);
            return self::$pdo;
        } catch (PDOException $e) {
            $errors[] = "odbc(Driver17): " . $e->getMessage();
        }

        // All four failed — dump everything
        die("<pre>Connection failed.\n\n" . implode("\n\n", array_map('htmlspecialchars', $errors)) . "</pre>");
    }
}