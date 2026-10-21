# Propuestas de Ian Rafael Avila Delgado

[Volver a la presentación de SIGHA](../README.md)

Mis propuestas relacionan los temas de clase con el desarrollo de SIGHA. Parto del informe y del UML que hemos construido en grupo. Las ideas siguientes son propuestas de trabajo y no afirman que el sistema ya esté implementado.

## Fundamentos de Python

### Semana como matriz

Propongo representar la consulta semanal mediante una lista de listas: las filas corresponderían a periodos y las columnas a días. Recorrería esa estructura con bucles para mostrar las asignaturas y los bloques libres de un curso. Comprobaría que cada clase aparezca en la posición que corresponda a su día y periodo.

## Clases objetos y métodos

### Horario relacionado con sus recursos

Propongo precisar el constructor de Horario para que reciba objetos de Asignatura, Docente, Aula, Curso y Periodo. Así desarrollaríamos las relaciones de nuestro UML en lugar de repetir sus datos en textos aislados. Comprobaría que consultar el detalle del horario permita identificar correctamente todos sus recursos.

## Encapsulamiento

### Horas protegidas y actualización controlada

Propongo que Periodo mantenga controladas sus horas y permita consultarlas mediante métodos o propiedades. Antes de actualizarlas, revisaría que el fin sea posterior al inicio. Comprobaría que un cambio inválido no modifique el periodo que ya era correcto, para proteger las asignaciones que lo utilizan.

## Herencia

### Datos comunes en Persona

Propongo reunir nombre e identificación en Persona y utilizarla como base de Docente y Estudiante. Con ello evitaríamos repetir su inicialización. Revisaría los atributos comunes y plantearía esta generalización como una actualización de nuestro UML, que actualmente presenta a ambas entidades por separado.

## Abstracción

### Contrato para las consultas

Propongo definir ConsultaHorario como una clase abstracta que requiera el método consultar(). Esta base indicaría qué operación deben ofrecer las consultas concretas. Antes de implementarla, acordaría sus parámetros y su resultado: una colección de horarios. La base no se utilizaría para crear consultas sin un criterio concreto.

## Polimorfismo

### Una llamada para distintos criterios

Propongo utilizar consultar() tanto con una ConsultaPorCurso como con una ConsultaPorDocente. Cada objeto aplicaría su criterio y respondería con las asignaciones correspondientes. Comprobaría el comportamiento con un curso y un docente que tengan clases diferentes; la llamada sería la misma, pero sus resultados podrían ser distintos.

## Relación con el trabajo del grupo

Mis ideas se complementan con las de los demás integrantes. Cuando dos propuestas se refieran a la misma clase u operación, las revisaremos juntas para acordar un solo diseño y evitar duplicaciones. Después podremos desarrollar y comprobar los cambios que adoptemos.
