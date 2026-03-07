# Fixes & Improvements — 2026-03-06

- no salen contra que equipo juega el próximo partido
- no salen los datos del equipo contra el que juega el próximo partido
- hay que indicar, dentro de la racha, cual es el último partido, el de la izq o el de la derecha?

- en los servicios, veo que hay un único método con todo el código ahí metido, existe la posibilidad de que agrupes funcionalidades y las pongas en un método privado dentro del service, y como nombre del método privado, algo explicativo de lo que se hace en ese método. Así se hace más fácil leer el método principal del servicio. Y juraría que no hay que tocar los test.
- borrar carpetas que no hayas creado para este proyecto, como por ejemplo la carpeta Controller
- me gustaría que las entidades no tengan el constructor público, si no que tengan un método público estático y que este llame al constructor privado.
- en las entity no me gusta que las funciones se llamen con get, prefiero que lo quites y dejes sólo el nombre, por ejemplo, el getName() se llamará name()