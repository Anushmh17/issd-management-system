<?php
require_once 'backend/db.php';
try {
    // 1. Drop the incorrect foreign key
    $pdo->exec("ALTER TABLE assignments DROP FOREIGN KEY assignments_ibfk_2");
    
    // 2. Add the correct foreign key referencing lecturers table
    $pdo->exec("ALTER TABLE assignments ADD CONSTRAINT fk_assignments_lecturer 
                FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE");
    
    echo "Successfully updated assignments table foreign key to reference lecturers(id).";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
