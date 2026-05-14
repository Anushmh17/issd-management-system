<?php
require 'backend/config.php';
require 'backend/db.php';
print_r($pdo->query('DESCRIBE activity_log')->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC));
