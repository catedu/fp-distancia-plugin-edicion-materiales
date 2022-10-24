<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

$string['pluginname'] = 'Educa Aragón';
$string['educaaragon:manageall'] = 'Manejar plugin local_educaaragon';
$string['educaaragon:editresources'] = 'Editar recursos editables en el curso';

$string['generalconfig'] = 'Configuración general';
$string['activetask'] = 'Activar tarea programada para transformar recursos';
$string['activetask_desc'] = 'Si se activa, una tarea programada del cron de Moodle recorrerá los cursos buscando contenidos SCORM e IMS para transformarlo';
$string['repository'] = 'Repositorio de contenidos';
$string['repository_desc'] = 'Seleccione el repositorio del tipo "filesystem" donde están almacenados todos los contenidos dinámicos en formato HTML. Si no existe ninguno, tendrá que crear uno y almacenar los contenidos en el. Los contenidos de cada curso deberán estar almacenados en carpetas nombradas con el nombre corto del curso para hacer la relación.';
$string['no_repository_exists'] = 'No existe ningún repositorio del tipo filesystem. Se necesita un repositorio con los contenidos de los cursos en HTML. Consulte con un desarrollador.';
$string['no_repository_select'] = 'No se ha seleccionado ningún repositorio en la configuración del plugin. Seleccione un repositorio antes de poder ejecutar la tarea.';
$string['allcourses'] = 'Aplicar a todos los cursos';
$string['allcourses_desc'] = 'Si está activada, la tarea programada aplicará a todos los cursos de la plataforma';
$string['category'] = 'Categoría';
$string['category_desc'] = 'Seleccione la categoría donde se aplicará la transformación de SCORMS e IMS a recursos. Todos los cursos contenidos en esta categoría se verán afectados, incluyendo los que estén en subcategorías';
$string['transformdynamiccontent'] = 'Tarea de transformación de contenidos dinámicos';
$string['course_processed'] = 'Curso procesado. Tiempo empleado: ';
$string['memory_used'] = 'Memoria utilizada: ';
$string['allcourses_processed'] = 'Todos los cursos procesados. Tiempo empleado: ';
$string['printable'] = 'imprimible';

$string['transform_dynamic_content_desc'] = 'Tarea para transformar SCORMS e IMS en recursos HTML y en su versión para imprimir. Después de pasar esta tarea, se podrán editar los contenidos de los cursos afectados.';
$string['notactivetask'] = 'Se ha desactivado la tarea programada desde configuración. No se modificará ningún curso.';
$string['coursesfound'] = 'Se van a procesar {$a} cursos';
$string['processcourse'] = 'Procesando curso {$a->shortname} con ID {$a->courseid}';
$string['errorprocesscourse'] = 'Error procesando curso. Revise los contenidos correspondientes al curso en el repositorio';
$string['errorprocesscourse_desc'] = 'Error procesando curso {$a->course}: {$a->error}';
$string['error/invalidpersistenterror'] = 'Hay errores de carácteres inválidos en los enlaces<br>error/invalidpersistenterror';
$string['error/invalidfilerequested'] = 'Hay recursos que contienen directorios, o archivos no válidos en su contenido<br>error/invalidfilerequested';
$string['dynamiccontent_found'] = 'Encontrados {$a} contenidos dinámicos';
$string['no_resourcegenerator'] = 'No existe generador de recursos en este entorno, por lo que la tarea no puede continuar. Contacte con un desarrollador.';
$string['no_associated_folder'] = 'No hay una carpeta asociada al curso {$a->course} en el repositorio {$a->repository}';
$string['elements_does_not_match'] = 'El número de recursos dinámicos del curso {$a->course} no coincide con el número de recursos asociados en el repositorio {$a->repository}. Este proceso no modificará nada en el curso hasta que esto se resuelva.';
$string['elements_cant_associate'] = 'No se han podido asociar los contenidos del curso {$a->course} con los contenidos del repositorio {$a->repository}. Por favor, revise los títulos de los recursos y la nomenclatura del contenido del repositorio. La numeración ha de ser 01, 02, 03, etc.';
$string['error_copy_files'] = 'Error al copiar los archivos del curso {$a->course}. Origen: {$a->origen} - Destino: {$a->destiny}. Resuelva esto antes de volver a ejecutar la tarea.';
$string['no_index_file'] = 'No se ha encontrado un archivo index.html en el recurso {$a->cmname} del curso {$a->course}. El proceso no continuará para este curso.';
$string['correctly_processed'] = 'Curso procesado correctamente';
$string['correctly_processed_needassociation'] = 'Curso procesado correctamente. Necesita ordenación manual de recursos editables';
$string['selected_for_reprocessing'] = 'Seleccionado para volver a procesarse en la siguiente ejecución de la tarea';
$string['resource_deleted'] = 'Se ha eliminado uno o varios recursos editable de este curso. Se recomienda volver a procesar.';
$string['processresource'] = 'Contenido creado en curso ';
$string['processlink'] = 'Procesado de enlaces de recurso ';

// Tables
$string['processedcourses'] = 'Cursos procesados';
$string['processedcourses_help'] = 'Listado de los cursos procesados por la tarea <b>local_educaaragon\task\transform_dynamic_content</b>.<br>Desde este panel podrá gestionar los cursos que necesite que se vuelvan a procesar en la siguiente ejecución de la tarea.';
$string['courseid'] = 'Id de curso';
$string['coursename'] = 'Nombre completo';
$string['shortname'] = 'Nombre corto';
$string['processed'] = 'Procesado';
$string['message'] = 'Mensaje';
$string['usermodified'] = 'Usuario';
$string['timemodified'] = 'Fecha de modificación';
$string['actions'] = 'Acciones';
$string['reprocessing'] = 'Reprocesar curso en la próxima ejecución';
$string['reprocessingmsg'] = '<p>Esta acción marcará este curso para que se vuelva a procesar en la próxima ejecución de la tarea programada <b>local_educaaragon\task\transform_dynamic_content</b>.</p><h4>¡ATENCIÓN!</h4><h5>Tenga en cuenta que al marcar este curso para que se vuelva a procesar se eliminarán los recursos que fueron generados anteriormente por la tarea, para evitar que se dupliquen.</h5>';
$string['reprocess'] = 'Reprocesar';
$string['editableresources'] = 'Mostrar la lista de recursos editables generados';
$string['editables'] = 'Recursos editables';
$string['editables_help'] = 'Listado de recursos disponibles para su edición.<br>Puede filtrar los resultados por curso añadiendo el parámetro "courseid" a la url.';
$string['resourceid'] = 'ID del recurso';
$string['resourcename'] = 'Nombre del recurso';
$string['viewcourse'] = 'Ver curso';
$string['backversions'] = 'Volver al selector de versiones';
$string['relatedcmid'] = 'Recurso relacionado';
$string['revieweditableresource'] = 'Ver recurso';
$string['editresource'] = 'Editar recurso';
$string['viewprintresource'] = 'Ver versión para impresión';

// Edit resource
$string['editingresource'] = 'Editando recurso';
$string['resourcenoteditable'] = 'Este recurso no es editable';
$string['versionnoteditable'] = 'Esta versión no se puede editar. Seleccione una versión diferente.';
$string['selectversion'] = 'Seleccione la versión';
$string['selectsection'] = 'Seleccione el apartado para editar';
$string['createnewversion'] = 'Crear una nueva versión';
$string['createnewversion_desc'] = '¿Seguro que quiere crear una nueva versión para editar?<br>El nombre que ha puesto a la versión será modificado para quitar carácteres especiales y sustituir espacios por -. Si lo ha dejado vacío, se pondrá la fecha en formato Unix como nombre de la versión';
$string['confirm'] = 'Confirmar';
$string['versionname'] = 'Nombre';
$string['loadversion'] = 'Editar version';
$string['deleteversion'] = 'Eliminar version';
$string['deleteversion_desc'] = '¿Seguro que quiere eliminar la versión seleccionada?<br>Tenga en cuenta que si esta versión está aplicada para su visualización, seguirá mostrándose a los usuarios aunque la elimine. Para solucionarlo, aplique otra versión.';
$string['asofversion'] = 'a partir de versión';
$string['versionalreadyexist'] = 'Ya existe una versión con ese nombre';
$string['errorcreateversion'] = 'Se ha producido un error al crear una nueva versión. Compruebe que el nombre no esté repetido ni contenga caráteres especiales y vuelva a intentarlo recargando esta página. Si el problema persiste póngase en contacto con un administrador.';
$string['save_changes'] = 'Guardar cambios';
$string['save_changes_desc'] = '¿Seguro que quiere guardar los cambios aplicados en esta versión? Los cambios se guardarán sobre la versión, no se aplicarán al recurso existente en el curso.';
$string['changes_saved'] = 'Cambios guardados correctamente: ';
$string['not_saved'] = 'No se han podido guardar los cambios, inténtelo de nuevo: ';
$string['apply_version'] = 'Aplicar versión';
$string['apply_version_desc'] = '¿Seguro que quiere aplicar la versión que está seleccionada al recurso que verán los estudiantes?';
$string['version_saved'] = 'La versión se ha aplicado correctamente: ';
$string['version_not_saved'] = 'No se ha podido aplicar la versión, inténtelo de nuevo: ';
$string['versionprintable_saved'] = 'La versión de impresión se ha aplicado correctamente a partir de la versión editada: ';
$string['versionprintable_not_saved'] = 'No se ha podido aplicar la versión de impresión, inténtelo de nuevo: ';

// Edited resource
$string['registereditions'] = 'Registro de ediciones';
$string['version_created'] = 'Nueva versión creada';
$string['version_created_asofversion'] = 'Creada a partir de la versión: ';
$string['version_deleted'] = 'Versión eliminada';
$string['version_changes_saved'] = 'Cambios guardados';
$string['version_changes_saved_file'] = 'Archivo afectado: ';
$string['version_applied'] = 'Versión aplicada al recurso';
$string['version_printable_applied'] = 'Versión imprimible aplicada al recurso';
$string['version_original_created'] = 'Versión original creada';
$string['action'] = 'Evento';
$string['other'] = 'Información adicional';
$string['version'] = 'Versión';
$string['edit_comments'] = 'Comentarios del editor: ';
$string['write_comment'] = 'Información adicional sobre la edición: ';

// Links
$string['link_report'] = 'Informe de enlaces';
$string['link_report_desc'] = 'En este informe se puede ver información sobre los enlaces que contiene una versión concreta de un recurso editable.';
$string['processresourcelinks'] = 'Buscando enlaces rotos y contenido flash en los recursos';
$string['link_case'] = 'Caso';
$string['link'] = 'Enlace';
$string['video'] = 'Video';
$string['iframe'] = 'Iframe';
$string['file'] = 'Archivo';
$string['link_type'] = 'Tipo de enlace:';
$string['link_text'] = 'Texto del enlace: ';
$string['link_active'] = 'Enlace activo';
$string['link_broken'] = 'Enlace roto';
$string['link_broken_cantfix'] = 'Enlace roto. No se soluciona con https';
$string['link_fixed'] = 'Enlace arreglado con https';
$string['link_flash'] = 'Contenido Flash';
$string['link_notvalid'] = 'La URL parece no válida, y no funciona';
$string['link_notvalid_active'] = 'La URL parece no válida, pero funciona';
$string['link_youtube'] = 'Enlace youtube válido';
$string['link_youtube_fixed'] = 'Enlace youtube arreglado';
$string['link_youtube_broken'] = 'Enlace youtube roto';
$string['showactivelinks'] = 'Mostrar enlaces activos';
$string['hideactivelinks'] = 'Ocultar enlaces activos';
$string['link_broken_afterchangehttps'] = 'Link roto tras aplicar https, funciona con http';
$string['process_resource_links'] = 'Enlaces de recurso procesados';
$string['process_version_links'] = 'Procesar los enlaces de la versión';
$string['process_version_links_desc'] = 'Se procesarán todos los enlaces de esta versión para detectar o arreglar enlaces que no funcionan.<br>Cuando termine el proceso, se le redirigirá al informe de enlaces para esta versión, pero la versión no será aplicada y tendrá que aplicarla manualmente cuando la revise.<br>Este proceso puede tardar varios minutos, y no se detendrá aunque cierre la pestaña (si la cierra no será redirigido al terminar).<br>Todos los registros de procesados de enlaces para esta versión que se hayan generado anteriormente serán eliminados para volver a crearse.<h5>¿Está seguro de procesar los enlaces para esta versión?</h5>';
$string['view_version_links'] = 'Ver registro de enlaces procesados';
$string['processed_resource_links'] = 'Enlaces procesados correctamente: ';
$string['not_processed_resource_links'] = 'No se han podido procesar los enlaces, inténtelo de nuevo más tarde: ';
$string['numfiles'] = 'Archivos: ';
$string['numlinks'] = 'Enlaces: ';
$string['numlinksactive'] = 'Activos: ';
$string['numlinksfixed'] = 'Arreglados: ';
$string['numlinksbroken'] = 'Rotos: ';
$string['numlinksnotvalid'] = 'No válidos: ';
$string['numprocessed'] = 'Procesados: ';
$string['numprocessedcorrectly'] = 'Correctos: ';
$string['numprocessederror'] = 'Errores: ';
$string['numprocessedwarning'] = 'Sin carpeta: ';
$string['nofolder'] = 'Sin carpeta';

// Navigation Links
$string['nav_adm_cursos'] = 'Administración del sitio (cursos)';