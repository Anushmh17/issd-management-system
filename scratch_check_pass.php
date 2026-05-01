<?php
$hash = '$2y$10$lYlgfHVZTl.GtW/n.xBj2OCUMHcNnkI/YNEKPEc6pp6Xh9ZIgUvn.';
$pass = 'Admin@1234';
echo "Checking password: $pass\n";
echo password_verify($pass, $hash) ? "MATCH FOUND" : "NO MATCH";
?>
