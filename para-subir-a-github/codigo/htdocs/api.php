<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
ini_set('session.use_strict_mode','1');
ini_set('display_errors','0');
$sessionFolder=dirname(getenv('SIGHA_DB_PATH')?:__DIR__.'/_data/sigha.sqlite').'/sessions';
if(!is_dir($sessionFolder))mkdir($sessionFolder,0700,true);
session_save_path($sessionFolder);
session_name('sigha_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS']),'path'=>'/']);
session_start();
function reply(array $data,int $status=200): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function reject(string $message,int $code=400): never {reply(['error'=>$message],$code);}
try {
 $dbPath=getenv('SIGHA_DB_PATH')?:__DIR__.'/_data/sigha.sqlite';
 if(!is_file($dbPath))reject('No se encontró la base de datos del sitio.',503);
 $pdo=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $pdo->exec('PRAGMA busy_timeout=5000');
 $columns=$pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN,1);
 if(!in_array('auth_version',$columns,true))$pdo->exec('ALTER TABLE users ADD COLUMN auth_version INTEGER NOT NULL DEFAULT 0');
 if(!in_array('recovery_hash',$columns,true))$pdo->exec('ALTER TABLE users ADD COLUMN recovery_hash TEXT DEFAULT NULL');
 $pdo->exec('CREATE TABLE IF NOT EXISTS materials(id TEXT PRIMARY KEY,class_id TEXT,date TEXT,name TEXT,mime TEXT,content BLOB)');
 $method=$_SERVER['REQUEST_METHOD'];
 $input=$method==='POST'?json_decode(file_get_contents('php://input'),true):[];
 if(!is_array($input))reject('Solicitud inválida.');
 $action=$method==='GET'?($_GET['action']??'session'):($input['action']??'');
 if(isset($_SESSION['last'])&&time()-$_SESSION['last']>7200){$_SESSION=[];session_regenerate_id(true);}
 $_SESSION['last']=time();
 if(isset($_SESSION['user'])){
  $q=$pdo->prepare('SELECT * FROM users WHERE id=?');$q->execute([$_SESSION['user']['id']]);$currentUser=$q->fetch(PDO::FETCH_ASSOC);
  if(!$currentUser||(int)($currentUser['auth_version'])!==(int)($_SESSION['user']['auth_version']??0)){$_SESSION=[];session_regenerate_id(true);}
  else{unset($currentUser['password'],$currentUser['recovery_hash']);$_SESSION['user']=$currentUser;}
 }
 if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
 if($method==='POST'){
  if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))reject('La sesión cambió. Recarga la página.',403);
  $origin=$_SERVER['HTTP_ORIGIN']??'';
  if($origin&&$origin!==((!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'')))reject('Origen de solicitud inválido.',403);
 }
 if($action==='session')reply(['user'=>$_SESSION['user']??null,'csrf'=>$_SESSION['csrf']]);
 if($action==='registration_options'){
  if($method!=='GET')reject('Método inválido.',405);
  $catalog=json_decode($pdo->query('SELECT payload FROM state WHERE id=1')->fetchColumn(),true);
  reply(['courses'=>array_map(fn($course)=>['id'=>$course['id'],'name'=>$course['name']],$catalog['courses']??[])]);
 }
 if($action==='register'){
  if($method!=='POST')reject('Método inválido.',405);
  if(!empty($_SESSION['user']))reject('Cierra sesión antes de crear otra cuenta.',403);
  $name=$input['name']??null;$email=$input['email']??null;$password=$input['password']??null;$confirm=$input['confirm_password']??null;$course=$input['course']??null;
  if(!is_string($name)||!is_string($email)||!is_string($password)||!is_string($confirm)||!is_string($course))reject('Completa todos los campos.');
  $name=trim(preg_replace('/\s+/u',' ',$name));$email=strtolower(trim($email));
  if(preg_match_all('/./us',$name)<2||preg_match_all('/./us',$name)>150)reject('Escribe tu nombre completo (de 2 a 150 caracteres).');
  if(strlen($email)>100||!preg_match('/^e[0-9]+@live\.uleam\.edu\.ec$/',$email))reject('Usa tu correo institucional: e seguido de números y @live.uleam.edu.ec.');
  if(preg_match_all('/./us',$password)<12)reject('La contraseña debe tener al menos 12 caracteres.');
  if(strlen($password)>72)reject('La contraseña es demasiado larga. Usa una más corta.');
  if($password!==$confirm)reject('Las contraseñas no coinciden.');
  $bucket='register-'.hash('sha256',$_SERVER['REMOTE_ADDR']??'local');
  $q=$pdo->prepare('SELECT hits,expires FROM attempts WHERE bucket=?');$q->execute([$bucket]);$attempt=$q->fetch(PDO::FETCH_ASSOC);
  if($attempt&&$attempt['expires']>time()&&$attempt['hits']>=5)reject('Se alcanzó el límite de registros. Inténtalo en una hora.',429);
  $passwordHash=password_hash($password,PASSWORD_DEFAULT);$registrationCode=strtoupper(bin2hex(random_bytes(16)));
  $pdo->exec('BEGIN IMMEDIATE');
  try{
   $q=$pdo->prepare('SELECT id FROM users WHERE email=?');$q->execute([$email]);
   if($q->fetchColumn()){$pdo->exec('ROLLBACK');reject('Ya existe una cuenta con este correo. Inicia sesión con ella.',409);}
   $catalog=json_decode($pdo->query('SELECT payload FROM state WHERE id=1')->fetchColumn(),true);
   if(!in_array($course,array_column($catalog['courses']??[],'id'),true)){$pdo->exec('ROLLBACK');reject('Selecciona un curso disponible.');}
   if(count($catalog['students'])>=2000){$pdo->exec('ROLLBACK');reject('No hay cupos de registro disponibles. Contacta al administrador.');}
   $pdo->prepare('INSERT INTO users(email,name,role,teacher_id,course_id,password,auth_version,recovery_hash) VALUES(?,?,?,NULL,?,?,0,?)')->execute([$email,$name,'student',$course,$passwordHash,hash('sha256',$registrationCode)]);
   $catalog['students'][]=['id'=>'st-'.bin2hex(random_bytes(12)),'name'=>$name,'course'=>$course];
   $pdo->prepare('UPDATE state SET payload=?,revision=revision+1 WHERE id=1')->execute([json_encode($catalog,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
   $hits=$attempt&&$attempt['expires']>time()?(int)$attempt['hits']+1:1;
   $pdo->prepare('INSERT OR REPLACE INTO attempts VALUES(?,?,?)')->execute([$bucket,$hits,time()+3600]);
   $pdo->exec('COMMIT');
  }catch(Throwable $e){$pdo->exec('ROLLBACK');throw $e;}
  session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
  reply(['ok'=>true,'csrf'=>$_SESSION['csrf'],'recovery_code'=>implode('-',str_split($registrationCode,4))],201);
 }

 if($action==='reset_password'){
  if($method!=='POST')reject('Método inválido.',405);
  $email=$input['email']??null;$code=$input['recovery_code']??null;$new=$input['new_password']??null;$confirm=$input['confirm_password']??null;
  if(!is_string($email)||!is_string($code)||!is_string($new)||!is_string($confirm))reject('Completa todos los campos.');
  $email=strtolower(trim($email));$code=strtoupper(preg_replace('/[^a-zA-Z0-9]/','',$code));
  if(!preg_match('/^e[0-9]+@live\.uleam\.edu\.ec$/',$email))reject('Usa tu correo institucional @live.uleam.edu.ec.');
  if(preg_match_all('/./us',$new)<12||strlen($new)>72)reject('Usa una contraseña de al menos 12 caracteres y no demasiado larga.');
  if($new!==$confirm)reject('Las contraseñas nuevas no coinciden.');
  $bucket='reset-'.hash('sha256',($_SERVER['REMOTE_ADDR']??'local').'|'.$email);
  $q=$pdo->prepare('SELECT hits,expires FROM attempts WHERE bucket=?');$q->execute([$bucket]);$attempt=$q->fetch(PDO::FETCH_ASSOC);
  if($attempt&&$attempt['expires']>time()&&$attempt['hits']>=8)reject('Demasiados intentos. Espera quince minutos.',429);
  $q=$pdo->prepare('SELECT id,password,recovery_hash FROM users WHERE email=?');$q->execute([$email]);$target=$q->fetch(PDO::FETCH_ASSOC);
  if(!$target||!$target['recovery_hash']||strlen($code)!==32||!hash_equals($target['recovery_hash'],hash('sha256',$code))){
   $hits=$attempt&&$attempt['expires']>time()?(int)$attempt['hits']+1:1;
   $pdo->prepare('INSERT OR REPLACE INTO attempts VALUES(?,?,?)')->execute([$bucket,$hits,time()+900]);
   reject('El correo o el código de recuperación no son correctos.');
  }
  if(password_verify($new,$target['password']))reject('Elige una contraseña diferente de la anterior.');
  $q=$pdo->prepare('UPDATE users SET password=?,recovery_hash=NULL,auth_version=auth_version+1 WHERE id=? AND recovery_hash=? AND password=?');
  $q->execute([password_hash($new,PASSWORD_DEFAULT),$target['id'],$target['recovery_hash'],$target['password']]);
  if(!$q->rowCount())reject('El código ya fue usado o la cuenta cambió. Solicita uno nuevo.',409);
  $pdo->prepare('DELETE FROM attempts WHERE bucket=?')->execute([$bucket]);
  if(isset($_SESSION['user'])){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));}
  reply(['ok'=>true,'csrf'=>$_SESSION['csrf']]);
 }

 if($action==='login'){
  if($method!=='POST')reject('Método inválido.',405);
  $email=strtolower(trim((string)($input['email']??'')));
  if(!preg_match('/^e[0-9]+@live\.uleam\.edu\.ec$/',$email))reject('Usa tu correo institucional: e seguido de números y @live.uleam.edu.ec.');
  $bucket=hash('sha256',($_SERVER['REMOTE_ADDR']??'local'));
  $q=$pdo->prepare('SELECT hits, expires FROM attempts WHERE bucket=?');$q->execute([$bucket]);$a=$q->fetch(PDO::FETCH_ASSOC);
  if($a&&$a['expires']>time()&&$a['hits']>=12)reject('Demasiados intentos. Espera 10 minutos.',429);
  $q=$pdo->prepare('SELECT * FROM users WHERE email=?');$q->execute([$email]);$u=$q->fetch(PDO::FETCH_ASSOC);
  if(!$u||!password_verify((string)($input['password']??''),$u['password'])){
   $hits=$a&&$a['expires']>time()?(int)$a['hits']+1:1;
   $pdo->prepare('INSERT OR REPLACE INTO attempts VALUES(?,?,?)')->execute([$bucket,$hits,time()+600]);
   reject('Correo o contraseña incorrectos.',401);
  }
  $pdo->prepare('DELETE FROM attempts WHERE bucket=?')->execute([$bucket]);
  session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
  unset($u['password'],$u['recovery_hash']);$_SESSION['user']=$u;reply(['user'=>$u,'csrf'=>$_SESSION['csrf']]);
 }
 if($action==='logout'){$_SESSION=[];session_destroy();reply(['ok'=>true]);}
 $user=$_SESSION['user']??null;if(!$user)reject('Inicia sesión para continuar.',401);
 if($action==='recovery_code'){
  if($method!=='POST')reject('Método inválido.',405);
  $old=$input['current_password']??null;if(!is_string($old))reject('Escribe tu contraseña actual.');
  $bucket='code-'.$user['id'];$q=$pdo->prepare('SELECT hits,expires FROM attempts WHERE bucket=?');$q->execute([$bucket]);$attempt=$q->fetch(PDO::FETCH_ASSOC);
  if($attempt&&$attempt['expires']>time()&&$attempt['hits']>=8)reject('Demasiados intentos. Espera quince minutos.',429);
  $q=$pdo->prepare('SELECT password FROM users WHERE id=?');$q->execute([$user['id']]);$hash=$q->fetchColumn();
  if(!password_verify($old,$hash)){$hits=$attempt&&$attempt['expires']>time()?(int)$attempt['hits']+1:1;$pdo->prepare('INSERT OR REPLACE INTO attempts VALUES(?,?,?)')->execute([$bucket,$hits,time()+900]);reject('La contraseña actual no es correcta.');}
  $code=strtoupper(bin2hex(random_bytes(16)));
  $q=$pdo->prepare('UPDATE users SET recovery_hash=? WHERE id=? AND password=?');$q->execute([hash('sha256',$code),$user['id'],$hash]);
  if(!$q->rowCount())reject('La cuenta cambió. Vuelve a iniciar sesión.',409);
  $pdo->prepare('DELETE FROM attempts WHERE bucket=?')->execute([$bucket]);
  reply(['code'=>implode('-',str_split($code,4))]);
 }
 if($action==='users'){
  if($method!=='GET')reject('Método inválido.',405);
  if($user['role']!=='admin')reject('Solo el administrador puede consultar las cuentas.',403);
  reply(['users'=>$pdo->query('SELECT id,email,name,role,teacher_id,course_id,auth_version FROM users ORDER BY name,id')->fetchAll(PDO::FETCH_ASSOC)]);
 }
 if($action==='update_user'){
  if($method!=='POST')reject('Método inválido.',405);
  if($user['role']!=='admin')reject('Solo el administrador puede cambiar roles.',403);
  $id=filter_var($input['id']??null,FILTER_VALIDATE_INT);$newRole=$input['role']??null;
  if(!$id||!is_string($newRole)||!in_array($newRole,['admin','teacher','student'],true))reject('Selecciona una cuenta y un rol válidos.');
  if((int)$id===(int)$user['id']&&$newRole!=='admin')reject('No puedes quitarte tu propio acceso de administrador.');
  $catalog=json_decode($pdo->query('SELECT payload FROM state WHERE id=1')->fetchColumn(),true);
  $teacher=$newRole==='teacher'?($input['teacher_id']??null):null;$course=$newRole==='student'?($input['course_id']??null):null;
  if($newRole==='teacher'&&(!is_string($teacher)||!in_array($teacher,array_column($catalog['teachers'],'id'),true)))reject('Selecciona el docente al que se vinculará esta cuenta.');
  if($newRole==='student'&&(!is_string($course)||!in_array($course,array_column($catalog['courses'],'id'),true)))reject('Selecciona el curso del estudiante.');
  $q=$pdo->prepare('SELECT auth_version FROM users WHERE id=?');$q->execute([$id]);$version=$q->fetchColumn();if($version===false)reject('La cuenta no existe.',404);
  if((int)($input['auth_version']??-1)!==(int)$version)reject('Esta cuenta cambió. Actualiza la lista.',409);
  $q=$pdo->prepare('UPDATE users SET role=?,teacher_id=?,course_id=?,auth_version=auth_version+1 WHERE id=? AND auth_version=?');$q->execute([$newRole,$teacher,$course,$id,$version]);
  if(!$q->rowCount())reject('Esta cuenta cambió. Actualiza la lista.',409);
  if((int)$id===(int)$user['id'])$_SESSION['user']['auth_version']=(int)$version+1;
  reply(['ok'=>true]);
 }

 if($action==='change_password'){
  if($method!=='POST')reject('Método inválido.',405);

  $bucket='password-'.$user['id'];$q=$pdo->prepare('SELECT hits,expires FROM attempts WHERE bucket=?');$q->execute([$bucket]);$attempt=$q->fetch(PDO::FETCH_ASSOC);
  if($attempt&&$attempt['expires']>time()&&$attempt['hits']>=12)reject('Demasiados intentos. Espera diez minutos.',429);
  $old=$input['current_password']??null;$new=$input['new_password']??null;$confirm=$input['confirm_password']??null;
  if(!is_string($old)||!is_string($new)||!is_string($confirm))reject('Completa las tres contraseñas.');
  $q=$pdo->prepare('SELECT password FROM users WHERE id=?');$q->execute([$user['id']]);$hash=$q->fetchColumn();
  if(!password_verify($old,$hash)){
   $hits=$attempt&&$attempt['expires']>time()?(int)$attempt['hits']+1:1;
   $pdo->prepare('INSERT OR REPLACE INTO attempts VALUES(?,?,?)')->execute([$bucket,$hits,time()+600]);
   reject('La contraseña actual no es correcta.');
  }
  if(preg_match_all('/./us',$new)<12)reject('La nueva contraseña debe tener al menos 12 caracteres.');
  if(strlen($new)>72)reject('La nueva contraseña es demasiado larga. Usa una más corta.');
  if($new!==$confirm)reject('Las contraseñas nuevas no coinciden.');
  if(password_verify($new,$hash))reject('Elige una contraseña diferente de la actual.');
  $q=$pdo->prepare('UPDATE users SET password=?,auth_version=auth_version+1 WHERE id=? AND password=?');
  $q->execute([password_hash($new,PASSWORD_DEFAULT),$user['id'],$hash]);
  if(!$q->rowCount())reject('Tu cuenta cambió. Vuelve a iniciar sesión.',409);
  $pdo->prepare('DELETE FROM attempts WHERE bucket=?')->execute([$bucket]);
  $_SESSION['user']['auth_version']=(int)($user['auth_version']??0)+1;
  session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
  reply(['ok'=>true,'csrf'=>$_SESSION['csrf'],'user'=>$_SESSION['user']]);
 }
 $record=$pdo->query('SELECT payload, revision FROM state WHERE id=1')->fetch(PDO::FETCH_ASSOC);
 $state=json_decode($record['payload'],true);$revision=(int)$record['revision'];
 if($action==='material'){
  $q=$pdo->prepare('SELECT * FROM materials WHERE id=?');$q->execute([$_GET['id']??'']);$file=$q->fetch(PDO::FETCH_ASSOC);
  if(!$file)reject('Material no encontrado.',404);
  $assigned=null;foreach($state['classes'] as $c)if($c['id']===$file['class_id'])$assigned=$c;
  if(!$assigned||($user['role']==='teacher'&&$assigned['teacher']!==$user['teacher_id'])||($user['role']==='student'&&$assigned['course']!==$user['course_id']))reject('No puedes abrir este material.',403);
  header('Content-Type: '.$file['mime']);header('Content-Length: '.strlen($file['content']));header("Content-Security-Policy: sandbox; default-src 'none'");
  header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($file['name']));echo $file['content'];exit;
 }
 function scoped(array $s,array $u): array {
  if($u['role']==='admin')return $s;
  $s['classes']=array_values(array_filter($s['classes'],fn($c)=>$u['role']==='teacher'?$c['teacher']===$u['teacher_id']:$c['course']===$u['course_id']));
  $ids=array_column($s['classes'],'id');
  $s['topics']=array_filter($s['topics'],fn($k)=>in_array(explode('_',$k)[0],$ids,true),ARRAY_FILTER_USE_KEY);
  $s['students']=[];
  return $s;
 }
 if($action==='state')reply(['state'=>scoped($state,$user),'revision'=>$revision]);
 if($method!=='POST')reject('Método inválido.',405);
 if((int)($input['revision']??-1)!==$revision)reject('Otra cuenta actualizó la información. Recarga y vuelve a intentarlo.',409);
 if($action==='save_state'){
  if($user['role']!=='admin')reject('Solo el administrador puede gestionar horarios.',403);
  $s=$input['state']??null;if(!is_array($s))reject('Datos inválidos.');
  foreach(['subjects','teachers','rooms','courses','students','classes'] as $collection){
   if(!isset($s[$collection])||!is_array($s[$collection])||count($s[$collection])>2000)reject('Registros inválidos.');
   $seen=[];foreach($s[$collection] as $item){$id=$item['id']??'';if(!is_string($id)||!preg_match('/^[a-zA-Z0-9-]{1,80}$/',$id)||isset($seen[$id]))reject('Identificadores inválidos.');$seen[$id]=true;if($collection!=='classes'&&(!is_string($item['name']??null)||trim($item['name'])===''||strlen($item['name'])>500))reject('Completa el nombre del registro.');}
  }
  $find=fn($collection,$id)=>in_array($id,array_column($s[$collection],'id'),true);
  foreach($s['students'] as $student)if(!$find('courses',$student['course']??''))reject('El estudiante necesita un curso válido.');
  foreach($s['classes'] as $i=>$c){
   foreach(['subject'=>'subjects','teacher'=>'teachers','room'=>'rooms','course'=>'courses'] as $key=>$collection)if(!$find($collection,$c[$key]??''))reject('Asignación de clase inválida.');
   if(!is_int($c['day']??null)||$c['day']<0||$c['day']>4||!is_numeric($c['start']??null)||!is_numeric($c['end']??null)||$c['start']<8||$c['end']>18||$c['end']<=$c['start']||fmod($c['start']*2,1)!==0.0||fmod($c['end']*2,1)!==0.0)reject('Horario inválido. Usa intervalos de media hora.');
   foreach(array_slice($s['classes'],0,$i) as $other)if($c['day']===$other['day']&&$c['start']<$other['end']&&$other['start']<$c['end']&&($c['teacher']===$other['teacher']||$c['room']===$other['room']||$c['course']===$other['course']))reject('Hay un cruce de docente, aula o curso.');
  }
  $users=$pdo->query('SELECT teacher_id,course_id FROM users')->fetchAll(PDO::FETCH_ASSOC);
  foreach($users as $u){if($u['teacher_id']&&!$find('teachers',$u['teacher_id']))reject('Este docente tiene una cuenta vinculada y no se puede eliminar.');if($u['course_id']&&!$find('courses',$u['course_id']))reject('Este curso tiene una cuenta vinculada y no se puede eliminar.');}
  $s['topics']=array_filter($state['topics'],fn($k)=>$find('classes',explode('_',$k)[0]),ARRAY_FILTER_USE_KEY);
  $state=$s;
 } elseif($action==='topic'){
  if($user['role']!=='teacher')reject('Solo el docente asignado puede publicar esta sesión.',403);
  $id=$input['class_id']??'';$class=null;foreach($state['classes'] as $c)if($c['id']===$id)$class=$c;
  if(!$class||$class['teacher']!==$user['teacher_id'])reject('Esta clase no está asignada a tu cuenta.',403);
  $date=$input['date']??'';$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
  if(!$d||$d->format('Y-m-d')!==$date||(int)$d->format('N')-1!==$class['day'])reject('Fecha de sesión inválida.');
  $topic=[];foreach(['title','objective','contents','activity','materials','before'] as $k){$v=$input['topic'][$k]??'';if(!is_string($v)||strlen($v)>12000)reject('Contenido demasiado largo.');$topic[$k]=trim($v);}
  if($topic['title']==='')reject('Escribe el tema de la sesión.');
  $topic['files']=$state['topics'][$id.'_'.$date]['files']??[];
  if(isset($input['file'])){
   if(count($topic['files'])>=8)reject('Esta sesión ya tiene ocho materiales adjuntos.');
   $name=basename((string)($input['file']['name']??''));$extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
   if(!in_array($extension,['pdf','txt','py','png','jpg','jpeg'],true)||strlen($name)>180)reject('Adjunta un PDF, texto, Python o imagen válido.');
   $binary=base64_decode($input['file']['content']??'',true);
   if($binary===false||strlen($binary)>2*1024*1024||strlen($binary)===0)reject('El archivo debe pesar entre 1 byte y 2 MB.');
   $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($binary);
   $expected=['pdf'=>['application/pdf'],'txt'=>['text/plain'],'py'=>['text/plain','text/x-script.python','text/x-python'],'png'=>['image/png'],'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg']];
   if(!in_array($mime,$expected[$extension],true))reject('El contenido del archivo no coincide con su extensión.');
   $fileId=bin2hex(random_bytes(16));$pendingFile=[$fileId,$id,$date,$name,$mime,$binary];$topic['files'][]=['id'=>$fileId,'name'=>$name];
  }
  $state['topics'][$id.'_'.$date]=$topic;
 } else reject('Acción no disponible.',404);
 $payload=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 if(strlen($payload)>4000000)reject('Se alcanzó el límite de información.');
 $pdo->beginTransaction();
 $q=$pdo->prepare('UPDATE state SET payload=?,revision=revision+1 WHERE id=1 AND revision=?');$q->execute([$payload,$revision]);
 if(!$q->rowCount()){$pdo->rollBack();reject('Otra cuenta actualizó la información. Recarga y vuelve a intentarlo.',409);}
 if(isset($pendingFile))$pdo->prepare('INSERT INTO materials VALUES(?,?,?,?,?,?)')->execute($pendingFile);
 $pdo->commit();
 reply(['state'=>scoped($state,$user),'revision'=>$revision+1]);
} catch(Throwable $e){error_log('SIGHA: '.$e->getMessage());reply(['error'=>'No se pudo completar la operación. Inténtalo de nuevo.'],500);}

