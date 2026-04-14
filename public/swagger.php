<?php
?>
<!DOCTYPE html>
<html>
<head>
  <title>Swagger API Activos</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
</head>

<body>
  <div id="swagger-ui"></div>

  <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
  <script>
    window.onload = () => {
      SwaggerUIBundle({
        url: "../docs/swagger.json",
        dom_id: "#swagger-ui",
        persistAuthorization: true // 🔥 mantiene el token
      });
    };
  </script>
</body>
</html>