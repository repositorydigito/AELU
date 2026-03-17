-- =============================================================================
-- FIX: Inscripciones con modalidad incorrecta - Replicación Febrero → Marzo 2026
-- Período afectado: Marzo 2026 (monthly_period_id = 15)
-- Fecha del script: 2026-03-16
-- =============================================================================
--
-- Problema: El job de replicación automática no incluía 'modality' en la clave
-- de búsqueda de talleres equivalentes. Cuando en marzo existían dos versiones
-- del mismo taller (Presencial y Virtual), el sistema asignó aleatoriamente
-- una de las dos sin respetar la modalidad original del alumno en febrero.
--
-- Alcance:
--   - 37 enrollments afectados por la replicación automática (37 enrollments / 21 batches activos)
--   - 1 caso adicional: ESTHER ARAKAKI (batch #1920, inscripción manual incorrecta)
--   - 3 casos adicionales detectados post-análisis: ROSA SHIMABUKURO DE NAKA (Act.Fís Sáb),
--     EUSEBIA OYAMA (Gimn Jue), ROSA OYAMA OYAMA (Gimn Jue)
--   - 1 caso adicional: IKEMIYASHIRO DE AGENA ROSA (Tai Chi Lun, inscripción 16/03/2026)
--   - Total: ~48 enrollments. Ejecutar PASO 0 para confirmar alcance real.
--   - ~174+ enrollment_classes a reasignar
--
-- Talleres involucrados:
--   ACTIVIDAD FISICA  | Virtual ↔ Presencial | Martes 11:00, Jueves 11:00, Sábado 11:00
--   GIMNASIA AERÓBICA | Virtual ↔ Presencial | Martes 12:00, Jueves 12:00
--   TAI CHI           | Virtual ↔ Presencial | Lunes 11:00, Viernes 11:00
--
-- Mapeo InstructorWorkshop (IW incorrecto → IW correcto):
--   IW #467 (Act.Fis Jue Virtual)    → IW #426 (Act.Fis Jue Presencial)
--   IW #466 (Act.Fis Mar Virtual)    → IW #409 (Act.Fis Mar Presencial)
--   IW #451 (Act.Fis Sáb Presencial) → IW #468 (Act.Fis Sáb Virtual)   [solo PAMELA HIGA #3888]
--   IW #468 (Act.Fis Sáb Virtual)    → IW #451 (Act.Fis Sáb Presencial)
--   IW #470 (Gimn Jue Virtual)       → IW #401 (Gimn Jue Presencial)
--   IW #469 (Gimn Mar Virtual)       → IW #396 (Gimn Mar Presencial)
--   IW #471 (Tai Chi Lun Virtual)    → IW #389 (Tai Chi Lun Presencial)
--   IW #472 (Tai Chi Vie Virtual)    → IW #439 (Tai Chi Vie Presencial)
--   + inversas para el caso ESTHER ARAKAKI (Batch #1920)
--
-- INSTRUCCIONES:
--   0. PRIMERO ejecutar el PASO 0 (diagnóstico) para verificar el alcance completo
--   1. Ejecutar primero en base de datos LOCAL y verificar los SELECTs de validación
--   2. Si todo es correcto, ejecutar en PRODUCCIÓN
--   3. En caso de error, ejecutar ROLLBACK antes de cerrar la sesión
-- =============================================================================

-- =============================================================================
-- PASO 0: DIAGNÓSTICO — Ejecutar ANTES del fix para detectar todos los casos
-- con modalidad incorrecta en Marzo 2026.
-- Compara la modalidad del workshop del student_enrollment vs la del
-- enrollment_class. Si difieren, la inscripción está mal asignada.
-- El resultado debe ser 0 filas DESPUÉS de ejecutar el fix.
-- =============================================================================

SELECT
    CONCAT(s.last_names, ' ', s.first_names) AS alumno,
    se.id                               AS enrollment_id,
    eb.id                               AS batch_id,
    eb.payment_status,
    w_se.name                           AS taller,
    w_se.day_of_week,
    w_se.start_time,
    w_se.modality                       AS modalidad_correcta,
    w_ec.modality                       AS modalidad_actual_en_clase,
    iw.id                               AS iw_actual,
    w_se.id                             AS ws_enrollment,
    w_ec.id                             AS ws_clase
FROM enrollment_classes ec
JOIN workshop_classes wc     ON ec.workshop_class_id    = wc.id
JOIN workshops w_ec          ON wc.workshop_id           = w_ec.id
JOIN student_enrollments se  ON ec.student_enrollment_id = se.id
JOIN instructor_workshops iw ON se.instructor_workshop_id = iw.id
JOIN workshops w_se          ON iw.workshop_id            = w_se.id
JOIN enrollment_batches eb   ON se.enrollment_batch_id    = eb.id
JOIN students s              ON eb.student_id             = s.id
WHERE w_se.monthly_period_id = 15
  AND eb.payment_status IN ('pending', 'to_pay', 'completed')
  AND w_ec.modality != w_se.modality
GROUP BY se.id, CONCAT(s.last_names, ' ', s.first_names), eb.id, eb.payment_status,
         w_se.name, w_se.day_of_week, w_se.start_time,
         w_se.modality, w_ec.modality, iw.id, w_se.id, w_ec.id
ORDER BY CONCAT(s.last_names, ' ', s.first_names), w_se.name, w_se.day_of_week;

-- =============================================================================

START TRANSACTION;

-- =============================================================================
-- BLOQUE 1: CASO ESTHER ARAKAKI DE YAMASHIRO (Batch #1920 - inscripción manual)
-- Todos sus talleres deben ser Virtual (era Virtual en febrero)
-- Enrollment IDs: 4164, 4165, 4166, 4167, 4168, 4169, 4170
-- =============================================================================

-- 1A. Corregir instructor_workshop_id (Presencial → Virtual)
UPDATE student_enrollments
SET
    instructor_workshop_id = CASE
        WHEN instructor_workshop_id = 409 THEN 466   -- Act.Fis Mar: Presencial(WS#408) → Virtual(WS#465)
        WHEN instructor_workshop_id = 426 THEN 467   -- Act.Fis Jue: Presencial(WS#425) → Virtual(WS#466)
        WHEN instructor_workshop_id = 451 THEN 468   -- Act.Fis Sáb: Presencial(WS#450) → Virtual(WS#467)
        WHEN instructor_workshop_id = 396 THEN 469   -- Gimn Mar:    Presencial(WS#395) → Virtual(WS#468)
        WHEN instructor_workshop_id = 401 THEN 470   -- Gimn Jue:    Presencial(WS#400) → Virtual(WS#469)
        WHEN instructor_workshop_id = 389 THEN 471   -- TaiChi Lun:  Presencial(WS#388) → Virtual(WS#470)
        WHEN instructor_workshop_id = 439 THEN 472   -- TaiChi Vie:  Presencial(WS#438) → Virtual(WS#471)
        ELSE instructor_workshop_id
    END,
    updated_at = NOW()
WHERE id IN (4164, 4165, 4166, 4167, 4168, 4169, 4170);

-- 1B. Corregir enrollment_classes (WC Presencial → WC Virtual, misma fecha)

-- Act.Fis Martes: WS#408(Presencial) → WS#465(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 408
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 465          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4164;

-- Act.Fis Jueves: WS#425(Presencial) → WS#466(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 425
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 466          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4167;

-- Act.Fis Sábado: WS#450(Presencial) → WS#467(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 450
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 467          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4168;

-- Gimnasia Martes: WS#395(Presencial) → WS#468(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 395
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 468          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4165;

-- Gimnasia Jueves: WS#400(Presencial) → WS#469(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 400
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 469          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4169;

-- Tai Chi Lunes: WS#388(Presencial) → WS#470(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 388
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 470          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4166;

-- Tai Chi Viernes: WS#438(Presencial) → WS#471(Virtual)
UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 438
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 471          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 4170;


-- =============================================================================
-- BLOQUE 2: CASOS POR REPLICACIÓN AUTOMÁTICA
-- Enrollments con modality incorrecta producto del bug en findEquivalentInstructorWorkshop
-- Solo batches activos (completed / to_pay)
-- =============================================================================

-- =============================================================================
-- 2A. ACTIVIDAD FISICA JUEVES 11:00 — Virtual(WS#466) → Presencial(WS#425)
-- Alumnos: OLGA TAIRA, TERESA SUECO SHIMABUKURO, ALBERTO RAUL JARA, ROSA YAMASHIRO
-- Enrollments: 4405, 4424, 4454, 4598
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 426, updated_at = NOW()   -- IW #467(Virtual) → IW #426(Presencial)
WHERE id IN (4405, 4424, 4454, 4598)
  AND instructor_workshop_id = 467;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 466
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 425          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4405, 4424, 4454, 4598);

-- =============================================================================
-- 2B. ACTIVIDAD FISICA MARTES 11:00 — Virtual(WS#465) → Presencial(WS#408)
-- Alumnos: OLGA TAIRA, ROSA SHIMABUKURO DE NAKA, TERESA SUECO SHIMABUKURO
-- Enrollments: 4404, 4416, 4430
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 409, updated_at = NOW()   -- IW #466(Virtual) → IW #409(Presencial)
WHERE id IN (4404, 4416, 4430)
  AND instructor_workshop_id = 466;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 465
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 408          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4404, 4416, 4430);

-- =============================================================================
-- 2C. ACTIVIDAD FISICA SÁBADO 11:00 — Presencial(WS#450) → Virtual(WS#467)
-- Alumno: PAMELA HIGA YAKA (único caso Virtual→Presencial en Sábado)
-- Enrollment: 3888
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 468, updated_at = NOW()   -- IW #451(Presencial) → IW #468(Virtual)
WHERE id = 3888
  AND instructor_workshop_id = 451;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 450
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 467          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 3888;

-- =============================================================================
-- 2D. ACTIVIDAD FISICA SÁBADO 11:00 — Virtual(WS#467) → Presencial(WS#450)
-- Alumnos: JULIA NAKAMINE, JULIA NAKAHARA, OLINDA YAGI, OLGA TAIRA, TOMOKO TAIRA,
--          ROSA SHIMABUKURO, TERESA SUECO, ESTHER AMEMIYA, CARMEN NAKAMINE, JUANA OSHIRO, ROSA YAMASHIRO
-- Enrollments: 4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 451, updated_at = NOW()   -- IW #468(Virtual) → IW #451(Presencial)
WHERE id IN (4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596)
  AND instructor_workshop_id = 468;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 467
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 450          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596);

-- =============================================================================
-- 2E. GIMNASIA AERÓBICA JUEVES 12:00 — Virtual(WS#469) → Presencial(WS#400)
-- Alumnos: JULIA NAKAMINE, TERESA SUECO, CARMEN NAKAMINE, ZOILA NISHIHIRA,
--          ROSA ODO, ROSA SAKUDA
-- Enrollments: 4395, 4431, 4460, 4533, 4573, 4575
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 401, updated_at = NOW()   -- IW #470(Virtual) → IW #401(Presencial)
WHERE id IN (4395, 4431, 4460, 4533, 4573, 4575)
  AND instructor_workshop_id = 470;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 469
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 400          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4395, 4431, 4460, 4533, 4573, 4575);

-- =============================================================================
-- 2F. GIMNASIA AERÓBICA MARTES 12:00 — Virtual(WS#468) → Presencial(WS#395)
-- Alumnos: ROSA SHIMABUKURO DE NAKA, TERESA SUECO, ELENA SHIMABUKURO, ROSA YAMASHIRO
-- Enrollments: 4415, 4429, 4587, 4597
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 396, updated_at = NOW()   -- IW #469(Virtual) → IW #396(Presencial)
WHERE id IN (4415, 4429, 4587, 4597)
  AND instructor_workshop_id = 469;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 468
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 395          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4415, 4429, 4587, 4597);

-- =============================================================================
-- 2G. TAI CHI LUNES 11:00 — Virtual(WS#470) → Presencial(WS#388)
-- Alumnos: SARA MIYASHIRO, ESTHER AMEMIYA, TOYO TAKARA, ETSUKO TSUKAZAN
-- Enrollments: 4387, 4448, 4640, 4647
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 389, updated_at = NOW()   -- IW #471(Virtual) → IW #389(Presencial)
WHERE id IN (4387, 4448, 4640, 4647)
  AND instructor_workshop_id = 471;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 470
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 388          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4387, 4448, 4640, 4647);

-- =============================================================================
-- 2H. TAI CHI VIERNES 11:00 — Virtual(WS#471) → Presencial(WS#438)
-- Alumnos: SARA MIYASHIRO, JULIA NAKAMINE, CARMEN NAKAMINE, JULIA YAGI
-- Enrollments: 4388, 4396, 4461, 4518
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 439, updated_at = NOW()   -- IW #472(Virtual) → IW #439(Presencial)
WHERE id IN (4388, 4396, 4461, 4518)
  AND instructor_workshop_id = 472;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 471
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 438          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id IN (4388, 4396, 4461, 4518);


-- =============================================================================
-- 2I. ACTIVIDAD FISICA SÁBADO 11:00 — Virtual(WS#467) → Presencial(WS#450)
-- Alumna: ROSA SHIMABUKURO DE NAKA (omitida en análisis original del bloque 2D)
-- =============================================================================

UPDATE student_enrollments se
JOIN enrollment_batches eb   ON se.enrollment_batch_id    = eb.id
JOIN students s              ON eb.student_id             = s.id
SET se.instructor_workshop_id = 451, se.updated_at = NOW()   -- IW Virtual(#468) → IW Presencial(#451)
WHERE s.last_names LIKE '%SHIMABUKURO DE NAKA%'
  AND se.instructor_workshop_id = 468;  -- IW #468 es específico de Marzo 2026

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id    = wc_wrong.id  AND wc_wrong.workshop_id = 467
JOIN workshop_classes wc_correct ON wc_correct.workshop_id  = 450          AND wc_correct.class_date = wc_wrong.class_date
JOIN student_enrollments se      ON ec.student_enrollment_id = se.id
JOIN enrollment_batches eb       ON se.enrollment_batch_id   = eb.id
JOIN students s                  ON eb.student_id            = s.id
SET ec.workshop_class_id = wc_correct.id
WHERE s.last_names LIKE '%SHIMABUKURO DE NAKA%';  -- wc_wrong.workshop_id = 467 ya limita al período

-- =============================================================================
-- 2J. GIMNASIA AERÓBICA JUEVES 12:00 — Virtual(WS#469) → Presencial(WS#400)
-- Alumna: EUSEBIA OYAMA (ticket 006-000131, omitida en análisis original del bloque 2E)
-- =============================================================================

UPDATE student_enrollments se
JOIN enrollment_batches eb   ON se.enrollment_batch_id    = eb.id
JOIN students s              ON eb.student_id             = s.id
SET se.instructor_workshop_id = 401, se.updated_at = NOW()   -- IW Virtual(#470) → IW Presencial(#401)
WHERE s.first_names LIKE '%EUSEBIA%'
  AND s.last_names  LIKE '%OYAMA%'
  AND se.instructor_workshop_id = 470;  -- IW #470 es específico de Marzo 2026

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id    = wc_wrong.id  AND wc_wrong.workshop_id = 469
JOIN workshop_classes wc_correct ON wc_correct.workshop_id  = 400          AND wc_correct.class_date = wc_wrong.class_date
JOIN student_enrollments se      ON ec.student_enrollment_id = se.id
JOIN enrollment_batches eb       ON se.enrollment_batch_id   = eb.id
JOIN students s                  ON eb.student_id            = s.id
SET ec.workshop_class_id = wc_correct.id
WHERE s.first_names LIKE '%EUSEBIA%'
  AND s.last_names  LIKE '%OYAMA%';  -- wc_wrong.workshop_id = 469 ya limita al período

-- =============================================================================
-- 2K. GIMNASIA AERÓBICA JUEVES 12:00 — Virtual(WS#469) → Presencial(WS#400)
-- Alumna: ROSA OYAMA OYAMA (ticket 006-000130, omitida en análisis original del bloque 2E)
-- =============================================================================

UPDATE student_enrollments se
JOIN enrollment_batches eb   ON se.enrollment_batch_id    = eb.id
JOIN students s              ON eb.student_id             = s.id
SET se.instructor_workshop_id = 401, se.updated_at = NOW()   -- IW Virtual(#470) → IW Presencial(#401)
WHERE s.first_names LIKE '%ROSA%'
  AND s.last_names  LIKE '%OYAMA OYAMA%'
  AND se.instructor_workshop_id = 470;  -- IW #470 es específico de Marzo 2026

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id    = wc_wrong.id  AND wc_wrong.workshop_id = 469
JOIN workshop_classes wc_correct ON wc_correct.workshop_id  = 400          AND wc_correct.class_date = wc_wrong.class_date
JOIN student_enrollments se      ON ec.student_enrollment_id = se.id
JOIN enrollment_batches eb       ON se.enrollment_batch_id   = eb.id
JOIN students s                  ON eb.student_id            = s.id
SET ec.workshop_class_id = wc_correct.id
WHERE s.first_names LIKE '%ROSA%'
  AND s.last_names  LIKE '%OYAMA OYAMA%';  -- wc_wrong.workshop_id = 469 ya limita al período


-- =============================================================================
-- 2L. TAI CHI LUNES 11:00 — Virtual(WS#470) → Presencial(WS#388)
-- Alumna: IKEMIYASHIRO DE AGENA ROSA (inscripción manual 16/03/2026, batch #2363)
-- Enrollment: 5092
-- =============================================================================

UPDATE student_enrollments
SET instructor_workshop_id = 389, updated_at = NOW()   -- IW #471(Virtual) → IW #389(Presencial)
WHERE id = 5092
  AND instructor_workshop_id = 471;

UPDATE enrollment_classes ec
JOIN workshop_classes wc_wrong   ON ec.workshop_class_id = wc_wrong.id   AND wc_wrong.workshop_id = 470
JOIN workshop_classes wc_correct ON wc_correct.workshop_id = 388          AND wc_correct.class_date = wc_wrong.class_date
SET ec.workshop_class_id = wc_correct.id
WHERE ec.student_enrollment_id = 5092;


-- =============================================================================
-- BLOQUE 3: ELIMINAR ASISTENCIAS INCORRECTAS
-- Se eliminan TODOS los registros de class_attendances para los enrollments
-- afectados, independientemente de si hay o no asistencia registrada.
-- Motivo: los registros apuntaban a workshops de modalidad incorrecta.
-- Los bloques 2A-2H usan IDs directos; 2I-2K usan subquery por nombre.
-- =============================================================================

-- 3A. Eliminar asistencias de enrollments con IDs conocidos (Bloques 1 y 2A-2H)
DELETE FROM class_attendances
WHERE student_enrollment_id IN (
    -- Bloque 1: Esther Arakaki
    4164, 4165, 4166, 4167, 4168, 4169, 4170,
    -- Bloque 2A-2H: Replicación automática
    4405, 4424, 4454, 4598,
    4404, 4416, 4430,
    3888,
    4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596,
    4395, 4431, 4460, 4533, 4573, 4575,
    4415, 4429, 4587, 4597,
    4387, 4448, 4640, 4647,
    4388, 4396, 4461, 4518,
    -- Bloque 2L: Ikemiyashiro De Agena Rosa
    5092
);

-- 3B. Eliminar asistencias de ROSA SHIMABUKURO DE NAKA — Act.Fís Sáb (Bloque 2I)
DELETE ca FROM class_attendances ca
JOIN student_enrollments se ON ca.student_enrollment_id = se.id
JOIN enrollment_batches eb  ON se.enrollment_batch_id   = eb.id
JOIN students s             ON eb.student_id             = s.id
WHERE s.last_names LIKE '%SHIMABUKURO DE NAKA%'
  AND se.instructor_workshop_id IN (451, 468);  -- antes y después del fix, por si acaso

-- 3C. Eliminar asistencias de EUSEBIA OYAMA — Gimn Jue (Bloque 2J)
DELETE ca FROM class_attendances ca
JOIN student_enrollments se ON ca.student_enrollment_id = se.id
JOIN enrollment_batches eb  ON se.enrollment_batch_id   = eb.id
JOIN students s             ON eb.student_id             = s.id
WHERE s.first_names LIKE '%EUSEBIA%'
  AND s.last_names  LIKE '%OYAMA%'
  AND se.instructor_workshop_id IN (401, 470);

-- 3D. Eliminar asistencias de ROSA OYAMA OYAMA — Gimn Jue (Bloque 2K)
DELETE ca FROM class_attendances ca
JOIN student_enrollments se ON ca.student_enrollment_id = se.id
JOIN enrollment_batches eb  ON se.enrollment_batch_id   = eb.id
JOIN students s             ON eb.student_id             = s.id
WHERE s.first_names LIKE '%ROSA%'
  AND s.last_names  LIKE '%OYAMA OYAMA%'
  AND se.instructor_workshop_id IN (401, 470);


-- =============================================================================
-- VALIDACIÓN FINAL — Ejecutar estos SELECTs antes del COMMIT para confirmar
-- que los cambios son correctos. Si algo no cuadra, hacer ROLLBACK.
-- =============================================================================

-- V1: Verificar que NO queden mismatches de modalidad en Marzo 2026 (debe dar 0 filas)
-- Esta query es idéntica al PASO 0 — si el fix fue completo, no debe devolver nada.
SELECT
    CONCAT(s.last_names, ' ', s.first_names) AS alumno,
    se.id                   AS enrollment_id,
    w_se.name               AS taller,
    w_se.modality           AS modalidad_enrollment,
    w_ec.modality           AS modalidad_clase
FROM enrollment_classes ec
JOIN workshop_classes wc     ON ec.workshop_class_id    = wc.id
JOIN workshops w_ec          ON wc.workshop_id           = w_ec.id
JOIN student_enrollments se  ON ec.student_enrollment_id = se.id
JOIN instructor_workshops iw ON se.instructor_workshop_id = iw.id
JOIN workshops w_se          ON iw.workshop_id            = w_se.id
JOIN enrollment_batches eb   ON se.enrollment_batch_id    = eb.id
JOIN students s              ON eb.student_id             = s.id
WHERE w_se.monthly_period_id = 15
  AND eb.payment_status IN ('pending', 'to_pay', 'completed')
  AND w_ec.modality != w_se.modality
GROUP BY se.id, CONCAT(s.last_names, ' ', s.first_names), w_se.name, w_se.modality, w_ec.modality
ORDER BY CONCAT(s.last_names, ' ', s.first_names);

-- V2: Verificar que NO queden enrollment_classes apuntando a workshops incorrectos
--     (debe retornar 0 filas)
SELECT ec.id, ec.student_enrollment_id, wc.workshop_id, w.modality, w.name
FROM enrollment_classes ec
JOIN workshop_classes wc ON ec.workshop_class_id = wc.id
JOIN workshops w ON wc.workshop_id = w.id
JOIN student_enrollments se ON ec.student_enrollment_id = se.id
JOIN instructor_workshops iw ON se.instructor_workshop_id = iw.id
JOIN workshops w2 ON iw.workshop_id = w2.id
WHERE ec.student_enrollment_id IN (
    4164, 4165, 4166, 4167, 4168, 4169, 4170,
    4405, 4424, 4454, 4598,
    4404, 4416, 4430,
    3888,
    4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596,
    4395, 4431, 4460, 4533, 4573, 4575,
    4415, 4429, 4587, 4597,
    4387, 4448, 4640, 4647,
    4388, 4396, 4461, 4518,
    5092
)
AND wc.workshop_id != w2.id;  -- enrollment_class apunta a workshop distinto al de la inscripción

-- V3: Verificar ESTHER ARAKAKI — todos deben ser Virtual ahora
SELECT se.id, w.name, w.modality, w.day_of_week
FROM student_enrollments se
JOIN instructor_workshops iw ON se.instructor_workshop_id = iw.id
JOIN workshops w ON iw.workshop_id = w.id
WHERE se.enrollment_batch_id = 1920
ORDER BY w.name, w.day_of_week;

-- V4: Confirmar que no quedaron asistencias para los enrollments corregidos (debe dar 0)
SELECT COUNT(*) AS asistencias_restantes
FROM class_attendances
WHERE student_enrollment_id IN (
    4164, 4165, 4166, 4167, 4168, 4169, 4170,
    4405, 4424, 4454, 4598,
    4404, 4416, 4430,
    3888,
    4394, 4397, 4403, 4406, 4407, 4419, 4425, 4450, 4459, 4507, 4596,
    4395, 4431, 4460, 4533, 4573, 4575,
    4415, 4429, 4587, 4597,
    4387, 4448, 4640, 4647,
    4388, 4396, 4461, 4518,
    5092
);


-- =============================================================================
-- Si las validaciones son correctas:
COMMIT;

-- Si hay algún problema:
-- ROLLBACK;
-- =============================================================================
