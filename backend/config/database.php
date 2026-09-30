<?php
declare(strict_types=1);

/*
 * EcoSwap database connection.
 *
 * Render PostgreSQL:
 *   Set DATABASE_URL to the Internal Database URL from Render.
 *
 * Local fallback:
 *   If DATABASE_URL is not set, the code uses MySQL environment variables.
 */

$databaseUrl = getenv("DATABASE_URL");

try {
    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);

        if ($parts === false || empty($parts["host"]) || empty($parts["path"])) {
            throw new RuntimeException("Invalid DATABASE_URL.");
        }

        $host = $parts["host"];
        $port = $parts["port"] ?? 5432;
        $dbname = ltrim($parts["path"], "/");
        $username = isset($parts["user"]) ? urldecode($parts["user"]) : "";
        $password = isset($parts["pass"]) ? urldecode($parts["pass"]) : "";

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

        $pdo = new PDO($dsn, $username, $password);
    } else {
        // Optional local MySQL fallback for XAMPP/WAMP.
        $host = getenv("DB_HOST") ?: "localhost";
        $dbname = getenv("DB_NAME") ?: "ecoswap";
        $username = getenv("DB_USER") ?: "root";
        $password = getenv("DB_PASSWORD") ?: "";

        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password
        );
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (Throwable $e) {
    http_response_code(500);

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed.",
        "error" => getenv("APP_DEBUG") === "1" ? $e->getMessage() : "Check DATABASE_URL and database availability."
    ]);

    exit;
}
?>
