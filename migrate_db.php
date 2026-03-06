<?php
include "koneksi.php";

// Add missing columns to users table
$columns_to_add = array(
    array(
        'column' => 'photo',
        'type' => 'VARCHAR(255) DEFAULT NULL',
        'query' => "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL"
    ),
    array(
        'column' => 'about',
        'type' => 'LONGTEXT DEFAULT NULL',
        'query' => "ALTER TABLE users ADD COLUMN about LONGTEXT DEFAULT NULL"
    )
);

$success_count = 0;
$error_messages = array();

foreach ($columns_to_add as $col) {
    // Check if column already exists
    $check_query = "SHOW COLUMNS FROM users LIKE '" . $col['column'] . "'";
    $result = mysqli_query($koneksi, $check_query);
    
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        if (mysqli_query($koneksi, $col['query'])) {
            $success_count++;
            echo "✓ Kolom '{$col['column']}' berhasil ditambahkan<br>";
        } else {
            $error_messages[] = "✗ Error menambahkan kolom '{$col['column']}': " . mysqli_error($koneksi);
            echo "✗ Error menambahkan kolom '{$col['column']}': " . mysqli_error($koneksi) . "<br>";
        }
    } else {
        echo "• Kolom '{$col['column']}' sudah ada<br>";
        $success_count++;
    }
}

echo "<hr>";
if (count($error_messages) == 0) {
    echo "<h3 style='color: green;'>✓ Semua kolom berhasil diproses!</h3>";
    echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a></p>";
} else {
    echo "<h3 style='color: red;'>Ada kesalahan:</h3>";
    foreach ($error_messages as $msg) {
        echo "<p>$msg</p>";
    }
}

mysqli_close($koneksi);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Database Migration</h1>
    <p>Proses menambahkan kolom ke tabel users...</p>
</body>
</html>
