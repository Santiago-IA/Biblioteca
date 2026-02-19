CREATE TABLE IF NOT EXISTS usuarios (
    id BIGSERIAL PRIMARY KEY,
    documento VARCHAR(30) UNIQUE NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(120),
    rol VARCHAR(20) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS libros (
    id BIGSERIAL PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    autor VARCHAR(160) NOT NULL,
    editorial VARCHAR(120) NOT NULL,
    anio INT,
    total_ejemplares INT NOT NULL CHECK (total_ejemplares >= 0)
);

CREATE TABLE IF NOT EXISTS prestamos (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT REFERENCES usuarios(id) ON DELETE RESTRICT,
    libro_id BIGINT REFERENCES libros(id) ON DELETE RESTRICT,
    fecha_prestamo DATE DEFAULT CURRENT_DATE,
    fecha_vencimiento DATE NOT NULL,
    fecha_devolucion DATE,
    observacion TEXT
);

-- Vista de disponibilidad
CREATE OR REPLACE VIEW vw_libros_disponibilidad AS
SELECT
    l.*,
    (l.total_ejemplares - COALESCE(p.prestados, 0)) AS disponibles
FROM libros l
LEFT JOIN (
    SELECT libro_id, COUNT(*) AS prestados
    FROM prestamos
    WHERE fecha_devolucion IS NULL
    GROUP BY libro_id
) p ON p.libro_id = l.id;