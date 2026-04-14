<?php
function validarRol($user, $roles) {
    if (!in_array($user->rol, $roles)) {
        echo json_encode(["error" => "Sin permisos"]);
        exit;
    }
}
?>