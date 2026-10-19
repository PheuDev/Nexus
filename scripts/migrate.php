<?php
require_once __DIR__ . '/../config/config.php';

$deadline = time() + 90;
while (true) {
    try {
        $pdo = db();
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
        break;
    } catch (Throwable $e) {
        if (time() >= $deadline) {
            fwrite(STDERR, "Database is not ready: {$e->getMessage()}\n");
            exit(1);
        }
        sleep(3);
    }
}
