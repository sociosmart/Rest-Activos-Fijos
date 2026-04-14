<?php
function logAccion($conn, $user_id, $accion) {
    pg_query_params(
        $conn,
        "INSERT INTO bitacora (user_id, accion) VALUES ($1,$2)",
        [$user_id, $accion]
    );
}
?>