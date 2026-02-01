<?php
// Mapea aquí los nombres REALES de tu BD.
// Si tu BD ya tiene devoluciones pero con otros nombres,
// solo cambias esto y el controller queda funcionando.

return [
    // tablas
    't_devoluciones' => 'tb_devoluciones',
    't_devoluciones_det' => 'tb_devoluciones_detalle',

    // columnas cabecera
    'c_id_devolucion' => 'id_devolucion',
    'c_id_venta' => 'id_venta',
    'c_id_usuario' => 'id_usuario',
    'c_fecha' => 'created_at',       // o fecha_devolucion
    'c_total' => 'total_devuelto',   // o total
    'c_motivo' => 'motivo',
    'c_estado' => 'estado',

    // columnas detalle
    'd_id_devolucion' => 'id_devolucion',
    'd_id_producto' => 'id_producto',   // o id_item
    'd_cantidad' => 'cantidad',
    'd_precio' => 'precio',             // opcional
];
