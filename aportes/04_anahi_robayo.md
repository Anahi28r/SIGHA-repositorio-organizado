# Propuestas de Anahi Carolina Robayo Tuarez

[Volver a la presentación de SIGHA](../README.md)

Mis propuestas relacionan los temas de clase con el desarrollo de SIGHA. Parto del informe y del UML que hemos construido en grupo. Las ideas siguientes son propuestas de trabajo y no afirman que el sistema ya esté implementado.

## Fundamentos de Python

### Selección y comprobación de días

Propongo utilizar una lista de días académicos y una función para convertir una selección numérica en su día correspondiente. Los condicionales revisarían el rango permitido y un bucle mostraría las opciones. Probaría selecciones válidas e inválidas para evitar que se construyan horarios con un día que no esté contemplado.

## Clases objetos y métodos

### Estudiantes relacionados con su curso

Propongo precisar los objetos Estudiante con los datos del UML y su relación con Curso. La consulta del estudiante obtendría el horario del curso o paralelo al que pertenece. Comprobaría que dos estudiantes del mismo grupo consulten la misma planificación, sin registrar por duplicado cada asignación de clase.

## Encapsulamiento

### Modificación segura de un horario

Propongo que modificarAsignacion() revise una propuesta completa antes de cambiar el horario existente. Primero se validarían día, periodo y recursos; después se comprobarían los cruces. Si la propuesta no es válida, conservaríamos la asignación anterior. Probaría un cambio de aula aceptado y otro rechazado por ocupación.

## Herencia

### Estudiante como tipo de Persona

Propongo que Estudiante herede de Persona y añada sus datos específicos y su relación con el curso. Revisaría qué métodos puede reutilizar y cuáles necesita definir de otra forma. Evitaría hacerlo heredar de Docente, porque esa relación no representa nuestro proyecto. Esta mejora tendría que reflejarse en una revisión del UML.

## Abstracción

### Persona con una operación requerida

Propongo estudiar Persona como clase abstracta y exigir una operación consultar_horario(horarios) para sus clases concretas. La base reuniría los datos comunes, mientras Docente y Estudiante completarían la consulta que les corresponde. Con ABC y abstractmethod se evitaría crear una Persona genérica o una hija que deje esa operación pendiente.

## Polimorfismo

### Consulta según el tipo de persona

Propongo llamar consultar_horario() tanto sobre un docente como sobre un estudiante. El docente obtendría sus clases y el estudiante las de su curso. Comprobaría ambos resultados con la misma colección de horarios. Cada clase implementaría su propio comportamiento, siguiendo la operación común definida para las personas.

## Relación con el trabajo del grupo

Mis ideas se complementan con las de los demás integrantes. Cuando dos propuestas se refieran a la misma clase u operación, las revisaremos juntas para acordar un solo diseño y evitar duplicaciones. Después podremos desarrollar y comprobar los cambios que adoptemos.

[Consultar el informe](../docs/INFORME_SIGHA.pdf) · [Consultar el UML](../docs/UML_SIGHA.pdf) · [Leer el reporte completo](../docs/APORTES_POR_TEMA.pdf)
