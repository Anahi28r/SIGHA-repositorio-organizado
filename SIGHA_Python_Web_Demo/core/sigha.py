#sigha.py — Lógica del sistema
#Contiene las clases de Python, como Docente, Asignatura, Aula, Curso, Periodo y BloqueHorario, además de funciones para validar horarios y detectar conflictos.
from dataclasses import dataclass
from datetime import datetime

HORA_MIN = 7 * 60
HORA_MAX = 18 * 60

def minutos(hora: str) -> int:
    """Convierte HH:MM a minutos después de medianoche y valida el formato."""
    if not isinstance(hora, str) or len(hora) != 5 or hora[2] != ":":
        raise ValueError("La hora debe tener el formato HH:MM (por ejemplo, 08:30).")
    try:
        dt = datetime.strptime(hora, "%H:%M")
    except ValueError:
        raise ValueError("La hora no es válida. Usa el formato HH:MM.")
    total = dt.hour * 60 + dt.minute
    if not HORA_MIN <= total <= HORA_MAX:
        raise ValueError("La hora debe estar entre 07:00 y 18:00.")
    return total

@dataclass
class Docente:
    id: int
    nombre: str
    especialidad: str = ""

@dataclass
class Asignatura:
    id: int
    nombre: str
    codigo: str

@dataclass
class Aula:
    id: int
    nombre: str
    capacidad: int
    tipo: str = "Aula"

@dataclass
class Curso:
    id: int
    nombre: str
    nivel: str = ""
    turno: str = "Matutino"

@dataclass
class Periodo:
    id: int
    nombre: str

@dataclass
class BloqueHorario:
    id: int
    asignatura_id: int
    docente_id: int
    aula_id: int
    curso_id: int
    dia: int
    hora_inicio: str
    hora_fin: str

    def validar(self):
        inicio = minutos(self.hora_inicio)
        fin = minutos(self.hora_fin)
        if fin <= inicio:
            raise ValueError("La hora de fin debe ser posterior a la hora de inicio.")
        if not 0 <= self.dia <= 4:
            raise ValueError("Selecciona un día entre lunes y viernes.")
        return inicio, fin

def se_superponen(a: BloqueHorario, b: BloqueHorario) -> bool:
    ai, af = a.validar()
    bi, bf = b.validar()
    return a.dia == b.dia and ai < bf and bi < af

def detectar_conflicto(nuevo: BloqueHorario, existentes: list[dict]) -> str | None:
    """Devuelve un mensaje si comparte docente, aula o curso en horas cruzadas."""
    ni, nf = nuevo.validar()
    for item in existentes:
        otro = BloqueHorario(**item)
        oi, of = otro.validar()
        if nuevo.dia != otro.dia or not (ni < of and oi < nf):
            continue
        if nuevo.docente_id == otro.docente_id:
            return "El docente ya tiene otra clase en ese horario."
        if nuevo.aula_id == otro.aula_id:
            return "El aula ya está ocupada en ese horario."
        if nuevo.curso_id == otro.curso_id:
            return "El curso ya tiene otra clase en ese horario."
    return None
