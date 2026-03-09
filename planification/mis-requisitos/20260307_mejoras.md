# Mejoras que he pedido

Listado de todo lo que he ido pidiendo, en orden cronológico.

---

## 2026-03-06

- ✅ No salía contra qué equipo juega el próximo partido
- ✅ No salían los datos del equipo contra el que juega el próximo partido
- ✅ Había que indicar dentro de la racha cuál es el partido más reciente (el de la izquierda)
- ✅ En los servicios, había un único método con todo el código ahí metido. Quería que agruparas funcionalidades en métodos privados con nombres explicativos para que el método principal sea más fácil de leer
- ✅ Borrar carpetas que no hubieras creado para este proyecto (por ejemplo la carpeta Controller que generó Symfony)
- ✅ Quería que las entidades no tuvieran el constructor público, sino un método público estático que llamara al constructor privado
- ✅ En las entidades no me gustaba que las funciones se llamaran con `get`. Quería que quitaras el prefijo y dejaras solo el nombre (por ejemplo `getName()` pasa a ser `name()`)

## 2026-03-08

- ✅ **[DISEÑO]** En la cabecera del partido quiero que aparezca primero el equipo local y luego el visitante, independientemente de cuál sea "nuestro" equipo seguido. Ahora siempre salía `nuestroEquipo vs rival`, aunque nuestro equipo jugara fuera. Debería salir `local vs visitante`
- ✅ **[BUG]** En el primer partido de la lista nunca aparecía la racha situacional del equipo rival (columna derecha de la card)
