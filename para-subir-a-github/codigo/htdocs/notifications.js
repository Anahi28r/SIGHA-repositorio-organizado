'use strict';
let reminderSnapshot=null,reminderRevision=-1,reminderTick=0;
function dueReminders(classes,account,now=new Date()){
 if(!account||!['teacher','student'].includes(account.role))return [];
 return classes.filter(c=>account.role==='teacher'?c.teacher===account.teacher_id:c.course===account.course_id).flatMap(c=>{
  if(c.day!==(now.getDay()+6)%7)return [];
  const start=new Date(now.getFullYear(),now.getMonth(),now.getDate(),Math.floor(c.start),Math.round(c.start%1*60));
  const remaining=start-now;
  return remaining>0&&remaining<=300000?[{classId:c.id,subject:c.subject,room:c.room,start:c.start,date:iso(now),key:c.id+'_'+iso(now)+'_'+c.start}]:[];
 });
}
function readNotices(){try{const list=JSON.parse(localStorage.getItem('sigha-notices-'+user.id)||'[]');return Array.isArray(list)?list:[]}catch{return []}}
function storeNotices(list){try{localStorage.setItem('sigha-notices-'+user.id,JSON.stringify(list.slice(0,60)))}catch{}}
function notificationButton(){return `<button class="notification-bell" popovertarget="notification-menu" aria-label="Notificaciones" title="Avisos de clase"><svg width="24" height="26" viewBox="0 0 24 26" fill="none" aria-hidden="true"><path d="M5 18h14l-2-3V10a5 5 0 0 0-10 0v5l-2 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 21a2 2 0 0 0 4 0M12 3v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="notification-count" id="notification-count" hidden></span></button><div id="notification-menu" class="notification-menu" popover><div class="notification-heading"><b>Notificaciones</b><small>5 minutos antes de tu clase</small></div><div id="notification-list"></div><div class="notification-settings"><p>Los avisos funcionan mientras SIGHA esté abierto. Tu equipo debe tener la hora correcta.</p><button class="textbtn" data-action="enable-notifications">Activar avisos del navegador</button><span id="notification-permission" role="status"></span></div></div>`}
function updateNotificationUI(){if(typeof user==='undefined'||!user||!$('#notification-list'))return;const notices=readNotices(),count=notices.filter(n=>!n.read).length;const badge=$('#notification-count');badge.hidden=count===0;badge.textContent=count>9?'9+':String(count);$('#notification-list').innerHTML=notices.length?notices.slice(0,15).map(n=>`<article class="notification-item ${n.read?'':'unread'}"><span>▦</span><div><b>${esc(n.title)}</b><p>${esc(n.body)}</p><small>${esc(n.date)} · ${time(n.start)}</small></div></article>`).join(''):'<p class="notification-empty">No tienes avisos todavía. Aquí aparecerán tus próximas clases cuando falten cinco minutos.</p>';const permission=$('#notification-permission');permission.textContent=!('Notification' in window)?'Este navegador muestra los avisos dentro de SIGHA.':Notification.permission==='granted'?'Avisos del navegador activados.':Notification.permission==='denied'?'Puedes permitir los avisos desde la configuración del navegador.':'';}
function checkClassReminders(now=new Date()){
 if(typeof user==='undefined'||!user||!['teacher','student'].includes(user.role))return;
 const source=reminderSnapshot&&reminderRevision>=revision?reminderSnapshot:db;
 let notices=readNotices();
 for(const due of dueReminders(source.classes,user,now)){
  if(notices.some(n=>n.key===due.key))continue;
  const subject=source.subjects.find(s=>s.id===due.subject)?.name||'Tu clase',room=source.rooms.find(r=>r.id===due.room)?.name||'Aula por asignar';
  const notice={...due,title:'Tu clase comienza pronto',body:subject+' · '+time(due.start)+' · '+room,read:false};
  notices.unshift(notice);storeNotices(notices);toast(notice.title+': '+notice.body);
  if('Notification' in window&&Notification.permission==='granted'){try{new Notification('SIGHA · '+notice.title,{body:notice.body,tag:'sigha-'+user.id+'-'+due.key})}catch{}}
 }
 updateNotificationUI();
}
async function syncReminderSchedule(){if(typeof user==='undefined'||!user||!['teacher','student'].includes(user.role))return;const accountId=user.id;try{const result=await api('state');if(user&&user.id===accountId){reminderSnapshot=result.state;reminderRevision=result.revision;checkClassReminders()}}catch{}}
document.addEventListener('toggle',e=>{if(e.target.id==='notification-menu'&&e.newState==='open'&&user){const list=readNotices().map(n=>({...n,read:true}));storeNotices(list);updateNotificationUI()}},true);
document.addEventListener('click',async e=>{if(!e.target.closest('[data-action="enable-notifications"]'))return;if(!('Notification' in window)){updateNotificationUI();return}try{await Notification.requestPermission()}catch{}updateNotificationUI()});
document.addEventListener('visibilitychange',()=>{if(!document.hidden){checkClassReminders();syncReminderSchedule()}});
window.addEventListener('storage',e=>{if(e.key?.startsWith('sigha-notices-'))updateNotificationUI()});
setInterval(()=>{checkClassReminders();if(++reminderTick%4===0)syncReminderSchedule()},15000);
