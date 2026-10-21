# Propuestas de Jose Gabriel Intriago Cedeño

[Volver a la presentación de SIGHA](../README.md)

Mis propuestas relacionan los temas de clase con el desarrollo de SIGHA. Parto del informe y del UML que hemos construido en grupo. Las ideas siguientes son propuestas de trabajo y no afirman que el sistema ya esté implementado.

## Fundamentos de Python

### Recorrer las asignaciones para buscar cruces

Propongo recorrer las asignaciones existentes y comparar cada una con la nueva propuesta. Con bucles y condicionales revisaría día, horas y recursos coincidentes. Prepararía casos de horas superpuestas, bloques consecutivos y días diferentes. Esta lógica básica ayudaría a desarrollar detectarConflicto() y seSuperponeCon(), que ya aparecen en nuestro UML.

## Clases objetos y métodos

### Docentes con disponibilidad individual

Propongo representar a cada docente mediante un objeto con nombre, identificación y disponibilidad. Definiría métodos para consultar y actualizar esa información. Utilizaría dos docentes de ejemplo para comprobar que modificar la disponibilidad de uno no altera la del otro, evitando tratar la disponibilidad como un dato compartido de toda la clase.

## Encapsulamiento

### Cambios de disponibilidad con revisión previa

Propongo controlar la actualización de disponibilidad del docente mediante un método. Primero acordaríamos un formato de días y horas, y después validaríamos los datos antes de sustituir los anteriores. También propondría revisar las asignaciones afectadas por el cambio. Comprobaría que un formato inválido no borre la disponibilidad que ya estaba registrada.

## Herencia

### Docente reutiliza la base Persona

Propongo que Docente herede los datos comunes de Persona y mantenga su disponibilidad como un atributo propio. Utilizaría super() para inicializar nombre e identificación y un método específico para describir su función académica. Comprobaría que el objeto conserve los datos heredados y los que corresponden únicamente al docente.

## Abstracción

### Operación común para consultar disponibilidad

Propongo definir un contrato de disponibilidad con una operación esta_ocupado(dia, periodo, horarios). Docente, Aula y Curso podrían ofrecer esa operación usando sus asignaciones. Acordaría primero los datos de entrada y el resultado esperado. Esta propuesta amplía el diseño y deberá revisarse antes de introducir nuevas abstracciones en el UML.

## Polimorfismo

### Disponibilidad según el recurso consultado

Propongo utilizar esta_ocupado() con distintos recursos. El docente buscaría sus clases, el aula revisaría su ocupación y el curso comprobaría sus asignaturas. Prepararía casos donde solo uno esté ocupado. La operación común permitiría obtener respuestas diferentes según el objeto, en lugar de resolver todos los casos con una única cadena de condicionales.

## Relación con el trabajo del grupo

Mis ideas se complementan con las de los demás integrantes. Cuando dos propuestas se refieran a la misma clase u operación, las revisaremos juntas para acordar un solo diseño y evitar duplicaciones. Después podremos desarrollar y comprobar los cambios que adoptemos.

[Consultar el informe](../docs/INFORME_SIGHA.pdf) · [Consultar el UML](../docs/UML_SIGHA.pdf) · [Leer el reporte completo](../docs/APORTES_POR_TEMA.pdf)
