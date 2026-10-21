# Propuestas de Heiko Marcelo Vera Ponce

[Volver a la presentación de SIGHA](../README.md)

Mis propuestas relacionan los temas de clase con el desarrollo de SIGHA. Parto del informe y del UML que hemos construido en grupo. Las ideas siguientes son propuestas de trabajo y no afirman que el sistema ya esté implementado.

## Fundamentos de Python

### Cálculo de horas planificadas

Propongo una función que recorra las asignaciones de una asignatura y acumule su duración. El resultado permitiría comparar las horas programadas con la carga semanal prevista. Utilizaría números, funciones, bucles y condicionales. Probaría una asignatura sin bloques y otra con varios periodos, considerando sus duraciones reales para evitar contar solo la cantidad de clases.

## Clases objetos y métodos

### Periodo con inicialización consistente

Propongo detallar Periodo con número, horaInicio y horaFin, de acuerdo con el UML. Un método de instancia permitiría consultar su duración. También estudiaría un método de clase para construir un periodo desde un formato de texto acordado y una operación estática para revisar ese formato, diferenciando self, cls y operaciones sin estado de instancia.

## Encapsulamiento

### Lista de estudiantes controlada por Curso

Propongo que Curso controle su colección de estudiantes y exponga operaciones para agregar, retirar y consultar sus integrantes. La consulta devolvería una copia o una vista que no permita alterar directamente la colección interna. Comprobaría que no se añada dos veces al mismo estudiante y que una consulta no modifique el curso accidentalmente.

## Herencia

### Sobrescritura del detalle de un aula

Propongo que un laboratorio pueda ampliar el método que presenta los datos de Aula. La versión especializada llamaría a super() para reutilizar número, capacidad y tipo, y añadiría la información de equipos. Revisaría que no se duplique la parte común. Esta idea complementa la propuesta de especialización del aula y no obliga a añadir otra clase diferente.

## Abstracción

### Resultados definidos para una consulta abstracta

Propongo completar el contrato de ConsultaHorario acordando sus parámetros y el formato de sus resultados. La operación abstracta exigiría consultar(), y las clases concretas devolverían una colección de horarios, incluso si está vacía. Así definiríamos qué puede esperar el resto del sistema antes de decidir cómo se realiza cada búsqueda.

## Polimorfismo

### Servicio que utiliza consultas concretas

Propongo un ServicioConsulta que reciba un objeto de consulta y utilice su método consultar(), sin decidir internamente si es por docente, curso o aula. Podríamos cambiar el criterio sustituyendo ese objeto. Comprobaría el servicio con consultas diferentes y con un recurso sin clases. La propuesta combina polimorfismo y composición, como en la estructura estudiada con las pasarelas.

## Relación con el trabajo del grupo

Mis ideas se complementan con las de los demás integrantes. Cuando dos propuestas se refieran a la misma clase u operación, las revisaremos juntas para acordar un solo diseño y evitar duplicaciones. Después podremos desarrollar y comprobar los cambios que adoptemos.

[Consultar el informe](../docs/INFORME_SIGHA.pdf) · [Consultar el UML](../docs/UML_SIGHA.pdf) · [Leer el reporte completo](../docs/APORTES_POR_TEMA.pdf)
