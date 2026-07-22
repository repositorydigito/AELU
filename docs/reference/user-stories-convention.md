# Convención — Historias de Usuario (user-stories)

> Cómo se escriben, marcan y evolucionan las historias de usuario en este proyecto. Aplica a `docs/modules/*/user-stories.md` y a `docs/specs/user-stories.md`.

---

## Dónde vive cada cosa

| Archivo | Contenido | Nivel |
|---------|-----------|-------|
| `docs/specs/new-requirements.md` | Roadmap **transversal**: épicas (A/B/C/D), orden de desarrollo, decisiones bloqueantes, % de avance global. Responde "**qué** construir, **por qué** y en **qué orden**". | Épica |
| `docs/modules/<dominio>/user-stories.md` | Historias detalladas **de ese dominio** (HU-Xnn), con criterios de aceptación y estado vivo. Responde "**cómo** se comporta este dominio". | Historia |
| `docs/specs/user-stories.md` | Índice global de historias / HUs que cruzan dominios. | Índice |

Las dos capas **se complementan, no se duplican**: una épica de `specs/` se descompone en HUs concretas dentro del módulo que corresponda. Cuando la épica cierra, sus HUs quedan marcadas ✅ en el módulo y la épica se marca ✅ en el roadmap.

---

## Estado de una historia (flag en el header)

Cada historia lleva su estado **en el propio header**, con emoji:

```
## HU-P03: Configurar Modalidad Por Horas  ·  ✅ Hecho
## HU-RPP-02: Filtro por caja  ·  🔶 En progreso
## HU-I11: Notificar saldo pendiente  ·  ⬜ Pendiente
```

| Flag | Significado |
|------|-------------|
| ✅ Hecho | Implementado y funcionando en el código. La historia describe comportamiento **actual**. |
| 🔶 En progreso | Parcialmente implementado o en desarrollo activo. |
| ⬜ Pendiente | Definido pero **no** implementado todavía. |
| ❌ Descartado | Superado por otro approach o fuera de alcance. No se borra; se conserva con una nota que explica por qué y qué lo reemplaza (rastro de la decisión). |

Los `- [ ]` / `- [x]` de la sección **Criterios de aceptación** siguen usándose para el detalle fino (qué criterio puntual ya se cumple). El flag del header = estado **global** de la historia.

---

## Cuando una historia se completa

**No se mueve a otro archivo.** Se queda en su módulo y se marca ✅. Razón: el archivo de módulo es la fuente única de "cómo se comporta este dominio"; sacar las hechas obliga a leer dos lugares y fragmenta la referencia. Una historia ✅ sigue siendo spec viva (referencia + base para regresiones).

Opcional — trazabilidad de cierre al final de la HU:

```
> ✅ Cerrado 2026-07 · ver docs/changelog/cambios-epica-a-c.md
```

---

## Cuando llega un requerimiento nuevo

1. ¿Es de un solo dominio? → **nueva HU en el `user-stories.md` de ese módulo**, con flag ⬜ Pendiente. La numeración continúa (HU-I09, HU-I10…).
2. ¿Cruza varios dominios / es una épica? → entra a `docs/specs/new-requirements.md` como épica, y se descompone en HUs por módulo a medida que se detalla.

**No** crear archivos "nuevos-requerimientos" por módulo ni archivos de "historias terminadas": recrean la fragmentación que esta estructura evita.

---

## Convención de numeración

- Prefijo por dominio: `HU-I` (inscripciones), `HU-P` (profesores), `HU-RPP` (reporte pago profesores), etc.
- Correlativo dentro del dominio, no se reutiliza aunque una HU quede obsoleta (marcar ⬜→ tachada o nota, pero conservar el número).

---

## Formato base de una HU (3 C's + INVEST)

```markdown
## HU-Xnn: Título breve  ·  ⬜ Pendiente

**Rol:** …
**Acción:** …
**Beneficio:** …

### Descripción
…

### Criterios de aceptación
- [ ] …
- [ ] …

### Conexión con el código
- Archivo/método relevante
```
