-- Migración opcional: eliminar columna quantity de user_configurations
-- Ejecutar solo si la tabla ya existía con la columna quantity (instalaciones anteriores).
-- Si la columna no existe (instalación nueva), ignorar el error.

USE fx_platform;

ALTER TABLE user_configurations DROP COLUMN quantity;
