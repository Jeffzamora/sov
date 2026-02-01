<?php
// Listado de clientes
$sql_clientes = "SELECT * FROM tb_clientes ORDER BY id_cliente DESC";
$query_clientes = $pdo->prepare($sql_clientes);
$query_clientes->execute();
$clientes_datos = $query_clientes->fetchAll(PDO::FETCH_ASSOC);
