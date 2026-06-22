<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    $email = 'admin@gmail.com';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Verificar si el usuario ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Actualizar contraseña y rol
        $stmt = $db->prepare("UPDATE usuarios SET password_hash = ?, rol = 'admin', activo = 1 WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "Usuario administrador actualizado correctamente.\n";
    } else {
        // Insertar nuevo usuario
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 'admin', 1)");
        $stmt->execute(['Admin', 'Sistema', $email, $hash]);
        echo "Usuario administrador creado correctamente.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
