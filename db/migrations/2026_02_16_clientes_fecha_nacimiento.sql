-- Migración: fecha de nacimiento y clientes menores (sin documento)

ALTER TABLE tb_clientes
  MODIFY numero_documento VARCHAR(60) NULL,
  ADD COLUMN fecha_nacimiento DATE NULL AFTER numero_documento;

-- (Opcional) índice para búsquedas por fecha
-- CREATE INDEX idx_clientes_fecha_nacimiento ON tb_clientes(fecha_nacimiento);

-- Nota: si tienes triggers de auditoría para tb_clientes, revisa que incluyan fecha_nacimiento.
