#app.py — Programa principal
#Utiliza Flask para levantar el servidor, recibir solicitudes y conectar la interfaz con la lógica del sistema.
from dataclasses import asdict
from flask import Flask, jsonify, render_template, request
from core.sigha import Docente, Asignatura, Aula, Curso, Periodo, BloqueHorario, detectar_conflicto

app = Flask(__name__)

# Datos de demostración en memoria. No se conecta a SIGHAWEB ni a su base de datos.
docentes = [
    Docente(1, "Carlos Mendoza", "Programación"),
    Docente(2, "Ana Ruiz", "Bases de datos"),
]
asignaturas = [
    Asignatura(1, "Programación Orientada a Objetos", "POO-101"),
    Asignatura(2, "Bases de Datos", "BD-102"),
]
aulas = [Aula(1, "Aula 201", 30, "Aula"), Aula(2, "Laboratorio 02", 25, "Laboratorio")]
cursos = [Curso(1, "Tecnologías de la Información · A", "Segundo", "Matutino")]
periodos = [Periodo(1, "2026-2")]
horarios = [
    BloqueHorario(1, 1, 1, 2, 1, 0, "08:00", "10:00"),
    BloqueHorario(2, 2, 2, 1, 1, 0, "10:00", "12:00"),
]
secuencias = {"docentes": 3, "asignaturas": 3, "aulas": 3, "cursos": 2, "horarios": 3}
DIAS = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"]

def serializar(lista):
    return [asdict(x) for x in lista]

def entidad_por_id(lista, ident):
    return next((x for x in lista if x.id == ident), None)

@app.get("/")
def inicio():
    return render_template("index.html")

@app.get("/api/datos")
def datos():
    return jsonify({
        "docentes": serializar(docentes),
        "asignaturas": serializar(asignaturas),
        "aulas": serializar(aulas),
        "cursos": serializar(cursos),
        "periodos": serializar(periodos),
        "horarios": serializar(horarios),
        "dias": DIAS,
    })

@app.post("/api/<tipo>")
def crear_entidad(tipo):
    cuerpo = request.get_json(silent=True) or {}
    if tipo == "docentes":
        nombre = str(cuerpo.get("nombre", "")).strip()
        especialidad = str(cuerpo.get("especialidad", "")).strip()
        if not nombre:
            return jsonify(error="Escribe el nombre del docente."), 400
        obj = Docente(secuencias[tipo], nombre, especialidad)
        docentes.append(obj)
    elif tipo == "asignaturas":
        nombre = str(cuerpo.get("nombre", "")).strip()
        codigo = str(cuerpo.get("codigo", "")).strip()
        if not nombre or not codigo:
            return jsonify(error="Completa el nombre y el código."), 400
        if any(a.codigo.lower() == codigo.lower() for a in asignaturas):
            return jsonify(error="Ese código de asignatura ya existe."), 400
        obj = Asignatura(secuencias[tipo], nombre, codigo)
        asignaturas.append(obj)
    elif tipo == "aulas":
        nombre = str(cuerpo.get("nombre", "")).strip()
        try:
            capacidad = int(cuerpo.get("capacidad", 0))
        except (TypeError, ValueError):
            capacidad = 0
        clase = str(cuerpo.get("tipo", "Aula")).strip() or "Aula"
        if not nombre or capacidad < 1:
            return jsonify(error="Escribe el nombre y una capacidad mayor que cero."), 400
        obj = Aula(secuencias[tipo], nombre, capacidad, clase)
        aulas.append(obj)
    elif tipo == "cursos":
        nombre = str(cuerpo.get("nombre", "")).strip()
        nivel = str(cuerpo.get("nivel", "")).strip()
        turno = str(cuerpo.get("turno", "Matutino")).strip()
        if not nombre:
            return jsonify(error="Escribe el nombre del curso."), 400
        obj = Curso(secuencias[tipo], nombre, nivel, turno)
        cursos.append(obj)
    else:
        return jsonify(error="Tipo de registro no válido."), 404
    secuencias[tipo] += 1
    return jsonify(asdict(obj)), 201

@app.post("/api/horarios")
def crear_horario():
    cuerpo = request.get_json(silent=True) or {}
    try:
        nuevo = BloqueHorario(
            id=secuencias["horarios"],
            asignatura_id=int(cuerpo["asignatura_id"]),
            docente_id=int(cuerpo["docente_id"]),
            aula_id=int(cuerpo["aula_id"]),
            curso_id=int(cuerpo["curso_id"]),
            dia=int(cuerpo["dia"]),
            hora_inicio=str(cuerpo["hora_inicio"]).strip(),
            hora_fin=str(cuerpo["hora_fin"]).strip(),
        )
        nuevo.validar()
    except (KeyError, TypeError, ValueError) as exc:
        return jsonify(error=str(exc) or "Completa todos los datos del horario."), 400
    if not entidad_por_id(asignaturas, nuevo.asignatura_id):
        return jsonify(error="Selecciona una asignatura existente."), 400
    if not entidad_por_id(docentes, nuevo.docente_id):
        return jsonify(error="Selecciona un docente existente."), 400
    if not entidad_por_id(aulas, nuevo.aula_id):
        return jsonify(error="Selecciona un aula existente."), 400
    if not entidad_por_id(cursos, nuevo.curso_id):
        return jsonify(error="Selecciona un curso existente."), 400
    conflicto = detectar_conflicto(nuevo, [asdict(h) for h in horarios])
    if conflicto:
        return jsonify(error=conflicto), 409
    horarios.append(nuevo)
    secuencias["horarios"] += 1
    return jsonify(asdict(nuevo)), 201

@app.delete("/api/horarios/<int:ident>")
def eliminar_horario(ident):
    horario = entidad_por_id(horarios, ident)
    if not horario:
        return jsonify(error="No se encontró ese horario."), 404
    horarios.remove(horario)
    return jsonify(ok=True)

if __name__ == "__main__":
    app.run(debug=True, host="127.0.0.1", port=5000)
