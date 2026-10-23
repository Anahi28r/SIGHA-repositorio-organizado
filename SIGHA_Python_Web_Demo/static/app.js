const $ = (s) => document.querySelector(s);
let data = null;
let vista = "horarios";
const dias = ["Lunes","Martes","Miércoles","Jueves","Viernes"];

async function api(url, options={}) {
  const response = await fetch(url, {
    headers: {"Content-Type":"application/json", ...(options.headers||{})},
    ...options
  });
  const body = await response.json();
  if (!response.ok) throw new Error(body.error || "Ocurrió un error.");
  return body;
}
function notice(message, error=false) {
  const n=$("#notice"); n.textContent=message; n.className=`notice ${error?"error":"ok"}`;
  window.scrollTo({top:0,behavior:"smooth"});
}
function label(list,id) { return list.find(x=>x.id===id)?.nombre || list.find(x=>x.id===id)?.name || "—"; }
function renderStats() {
  $("#stats").innerHTML = [
    ["Docentes",data.docentes.length],["Asignaturas",data.asignaturas.length],
    ["Aulas",data.aulas.length],["Bloques horarios",data.horarios.length]
  ].map(([t,n])=>`<div class="stat"><span>${t}</span><strong>${n}</strong></div>`).join("");
}
function selectField(labelText,name,items,field="nombre") {
  return `<div class="field"><label>${labelText}</label><select name="${name}" required>${items.map(x=>`<option value="${x.id}">${x[field] || x.nombre || x.name}</option>`).join("")}</select></div>`;
}
function render() {
  renderStats();
  document.querySelectorAll(".tab").forEach(b=>b.classList.toggle("active",b.dataset.view===vista));
  const view=$("#view");
  if(vista==="horarios") return renderHorarios(view);
  const config = {
    docentes:{title:"Docentes",sub:"Personas que imparten las asignaturas.",fields:[["Nombre","nombre","text"],["Especialidad","especialidad","text"]],endpoint:"docentes",items:data.docentes,detail:x=>x.especialidad||"Sin especialidad"},
    asignaturas:{title:"Asignaturas",sub:"Materias que forman parte de la planificación.",fields:[["Nombre","nombre","text"],["Código","codigo","text"]],endpoint:"asignaturas",items:data.asignaturas,detail:x=>x.codigo},
    aulas:{title:"Aulas",sub:"Espacios físicos y su capacidad.",fields:[["Nombre","nombre","text"],["Capacidad","capacidad","number"],["Tipo","tipo","text"]],endpoint:"aulas",items:data.aulas,detail:x=>`${x.capacidad} puestos · ${x.tipo}`},
    cursos:{title:"Cursos",sub:"Grupos de estudiantes a los que se asignan clases.",fields:[["Nombre","nombre","text"],["Nivel","nivel","text"],["Turno","turno","text"]],endpoint:"cursos",items:data.cursos,detail:x=>`${x.nivel||"Nivel sin indicar"} · ${x.turno}`}
  }[vista];
  view.innerHTML=`<section class="panel"><div class="panel-head"><div><h2>${config.title}</h2><div class="muted">${config.sub}</div></div><span class="tag">${config.items.length} registros</span></div>
  <div class="layout"><form id="createForm" class="panel"><h2>Agregar registro</h2><div class="form-grid">${config.fields.map(([l,n,t])=>`<div class="field"><label>${l}</label><input name="${n}" type="${t}" ${t==="number"?'min="1"':''} required></div>`).join("")}</div><div class="form-actions"><button class="btn">Guardar ${config.title.toLowerCase().slice(0,-1)}</button></div></form>
  <div><h2>Registros existentes</h2><div class="list">${config.items.map(x=>`<div class="item"><div><strong>${x.nombre}</strong><small>${config.detail(x)}</small></div><span class="tag">#${x.id}</span></div>`).join("")||'<div class="empty">Todavía no hay registros.</div>'}</div></div></div></section>`;
  $("#createForm").addEventListener("submit",async e=>{
    e.preventDefault(); const payload=Object.fromEntries(new FormData(e.currentTarget).entries());
    if(payload.capacidad) payload.capacidad=Number(payload.capacidad);
    try { await api(`/api/${config.endpoint}`,{method:"POST",body:JSON.stringify(payload)}); await load(); notice("Registro agregado correctamente."); }
    catch(err){notice(err.message,true);}
  });
}
function renderHorarios(view) {
  const ordered=[...data.horarios].sort((a,b)=>a.dia-b.dia||a.hora_inicio.localeCompare(b.hora_inicio));
  view.innerHTML=`<section class="panel"><div class="panel-head"><div><h2>Planificación semanal</h2><div class="muted">Las validaciones se realizan en Python antes de guardar un bloque.</div></div><span class="tag">${ordered.length} clases</span></div>
  <div class="layout"><form id="scheduleForm" class="panel"><h2>Crear horario</h2><div class="form-grid">
  ${selectField("Asignatura","asignatura_id",data.asignaturas)}
  ${selectField("Docente","docente_id",data.docentes)}
  ${selectField("Aula","aula_id",data.aulas)}
  ${selectField("Curso","curso_id",data.cursos)}
  <div class="field"><label>Día</label><select name="dia">${dias.map((d,i)=>`<option value="${i}">${d}</option>`).join("")}</select></div>
  <div class="field"><label>Hora de inicio</label><input name="hora_inicio" type="time" min="07:00" max="17:59" value="08:00" required></div>
  <div class="field"><label>Hora de fin</label><input name="hora_fin" type="time" min="07:01" max="18:00" value="10:00" required></div>
  </div><div class="form-actions"><button class="btn">Validar y guardar</button></div><p class="muted">Horario permitido: 07:00 a 18:00. No se permiten cruces de docente, aula o curso.</p></form>
  <div><h2>Clases programadas</h2><div class="list">${ordered.map(h=>`<div class="item"><div><strong>${label(data.asignaturas,h.asignatura_id)}</strong><small>${dias[h.dia]} · ${h.hora_inicio}–${h.hora_fin}</small><small>${label(data.docentes,h.docente_id)} · ${label(data.aulas,h.aula_id)}</small></div><button class="btn danger" data-delete="${h.id}" type="button">Eliminar</button></div>`).join("")||'<div class="empty">No hay clases programadas.</div>'}</div></div></div></section>`;
  $("#scheduleForm").addEventListener("submit",async e=>{
    e.preventDefault(); const payload=Object.fromEntries(new FormData(e.currentTarget).entries());
    ["dia","asignatura_id","docente_id","aula_id","curso_id"].forEach(k=>payload[k]=Number(payload[k]));
    try { await api("/api/horarios",{method:"POST",body:JSON.stringify(payload)}); await load(); notice("Horario validado y guardado."); }
    catch(err){notice(err.message,true);}
  });
  view.querySelectorAll("[data-delete]").forEach(b=>b.addEventListener("click",async()=>{
    if(!confirm("¿Eliminar este bloque horario de la demostración?")) return;
    try {await api(`/api/horarios/${b.dataset.delete}`,{method:"DELETE"});await load();notice("Horario eliminado.");}
    catch(err){notice(err.message,true);}
  }));
}
async function load(){data=await api("/api/datos");render();}
document.querySelectorAll(".tab").forEach(b=>b.addEventListener("click",()=>{vista=b.dataset.view;render();}));
load().catch(e=>notice(`No se pudieron cargar los datos: ${e.message}`,true));
