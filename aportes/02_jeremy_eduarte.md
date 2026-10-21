# Propuestas de Jeremy Jair Eduarte Lopez

[Volver a la presentación de SIGHA](../README.md)

Mis propuestas relacionan los temas de clase con el desarrollo de SIGHA. Parto del informe y del UML que hemos construido en grupo. Las ideas siguientes son propuestas de trabajo y no afirman que el sistema ya esté implementado.

## Fundamentos de Python

### Funciones para revisar las entradas

Propongo separar en funciones la lectura y revisión de datos básicos, como el número de periodo y la capacidad de un aula. Utilizaría conversiones y condicionales para aceptar valores coherentes. Probaría entradas válidas, valores vacíos y números fuera del rango previsto antes de incorporarlos a los recursos del sistema.

## Clases objetos y métodos

### Asignaturas con atributos y operaciones claras

Propongo detallar Asignatura con nombre, código y horas semanales, de acuerdo con el informe. Su constructor inicializaría cada objeto y sus métodos permitirían consultar sus datos. Crearía dos asignaturas de prueba para comprobar que sus valores son individuales y no se confunden con atributos compartidos de la clase.

## Encapsulamiento

### Carga semanal con valores válidos

Propongo controlar la modificación de las horas semanales de una asignatura mediante una operación específica. Esta operación verificaría que el nuevo valor sea un entero positivo antes de cambiarlo. Comprobaría que se rechacen valores negativos o nulos y que, después del rechazo, se conserve el valor anterior.

## Herencia

### Especialización de un aula

Propongo estudiar LaboratorioTI como una especialización de Aula que añada cantidad de equipos. Su constructor reutilizaría número y capacidad mediante super().__init__(). Antes de incorporarla al UML, revisaríamos si la planificación necesita distinguir ese tipo de espacio. La especialización ampliaría Aula, sin reemplazar los datos comunes.

## Abstracción

### Contrato para validar asignaciones

Propongo una base abstracta ValidadorAsignacion con una operación validar(candidato, horarios). Su responsabilidad sería definir qué recibe y qué devuelve una validación, sin resolver todos los conflictos en la clase base. Las implementaciones concretas revisarían docente, aula o curso siguiendo las reglas de Horario y Periodo.

## Polimorfismo

### Validaciones intercambiables

Propongo que ValidadorDocente, ValidadorAula y ValidadorCurso implementen la misma operación validar(). Un proceso podría recorrer esos objetos y recoger sus resultados sin cambiar la llamada para cada tipo. Comprobaría tres casos separados, cada uno con un único recurso en conflicto, para distinguir el comportamiento de las implementaciones.

## Relación con el trabajo del grupo

Mis ideas se complementan con las de los demás integrantes. Cuando dos propuestas se refieran a la misma clase u operación, las revisaremos juntas para acordar un solo diseño y evitar duplicaciones. Después podremos desarrollar y comprobar los cambios que adoptemos.

[Consultar el informe](../docs/INFORME_SIGHA.pdf) · [Consultar el UML](../docs/UML_SIGHA.pdf) · [Leer el reporte completo](../docs/APORTES_POR_TEMA.pdf)
