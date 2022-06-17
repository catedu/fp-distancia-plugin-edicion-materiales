# fp-distancia-plugin-edicion-materiales
Plugin de Moodle para la edición de materiales del ministerio

El plugin se instala como cualquier otro plugin Local, añadiendo los archivos dentro de la carpeta /local/educaaragon/ y pasando por la administración.

Durante la instalación, el plugin creará en la base de datos las tablas, servicios, eventos, tarea programada y capacidades que necesita para funcionar.

Repositorio
===========

Para que el plugin funcione, es necesario que se cree un repositorio dentro de Moodle del tipo “Sistema de archivos”, con cualquier nombre que permita identificarlo posteriormente.

Los pasos a seguir son los siguientes:

*   Crear una carpeta en **“moodledata/repository”** con el nombre del repositorio, y **dentro de ella crear una carpeta “editions”**. En esta carpeta estarán todos los recursos en formato HTML proporcionados por el cliente.
    
*   Dentro de la administración de Moodle, ir a **Administración del sitio→ Extensiones → Repositorios → Gestionar Repositorios → Sistema de archivos**, debe estar marcado como “Activado y visible”
    
*   Pinchando en **“configuración” del Sistema de archivos**, podremos **crear una nueva instancia de repositorio**, donde poremos **asociar la carpeta que hemos creado en “moodledata/repository”**
    
*   La configuración deberá quedar así (con el nombre que deseemos, y con la carpeta correspondiente seleccionada)

Contenido del repositorio
-------------------------

Dentro del repositorio que acabamos de crear, los contenidos que se van a utilizar para generar los nuevos recursos durante el procesamiento, deberán seguir los siguientes requisitos:

*   Deberá existir **una carpeta nombrada con el nombre corto del curso** al que corresponda
    
*   Dentro de la carpeta del curso, debe existir **una carpeta por cada recurso que se vaya a generar**, (recomendable que esté nombrada con 01, 02, 03… según el orden de aparición del recurso en el curso, para facilitar la ordenación).
    
*   **Dentro de cada carpeta de un recurso deberán estar todos los ficheros necesarios para que el contenido funcione correctamente, así cómo** **un fichero “index.html”** que será el que sirva de disparador del contenido. (Si este fichero no existe, el recurso no se generará).
    

Configuración
=============

Una vez instalado el plugin, para su configuración tendremos hay que ir a **Administración del sitio → Cursos → Educa Aragón → Ajustes generales**

Aquí podremos activar o desactivar el procesamiento de tareas.

Al activarla, se nos mostrarán distintas opciones:

*   **Activar tarea programada para transformar recursos:** activa o desactiva el procesamiento de cursos por la tarea programada (aunque la tarea se ejecute, si esta opción está desmarcada no se procesará ningún curso).
    
*   **Repositorio de contenidos:** selección del repositorio donde están contenidos todos los recursos exportados.
    
*   **Aplicar a todos los cursos:** si se marca esta casilla, todos los cursos de la plataforma serán procesados. Si se desmarca, aparecerá el selector de categorías de curso.
    
*   **Categoría:** el proceso de cursos sólo se harán sobre los cursos que pertenezcan a esta categoría (incluyendo los cursos de las subcategorías)
    

### Tarea Programada

Para configurar la tarea programada del plugin hay que ir a **Administración del sitio → Servidor → Tareas → Tareas Programadas** y en el listado buscar ““

Desde este panel podrá **configurar la tarea de la misma forma que cualquier otra tarea de moodle, o leer los registros que se han generado durante su ejecución.**

Documentación oficial para configurar tareas programadas: [https://docs.moodle.org/310/en/Scheduled\_tasks](https://docs.moodle.org/310/en/Scheduled_tasks)

Debido a la posible duración de la tarea y a que crea nuevos contenidos en el curso para los estudiantes finales, se recomienda configurar la tarea para que se ejecute una vez al díia en horario con poca concurrencia en la plataforma (por defecto, se crea configurada para que pase todos los días a las 3 a.m)

Independientemente del periodo de ejecución que se programe para esta tarea, **se recomienda configurar el cron para que se ejecute cada 30 segundos o cada minuto**, ya que este plugin utiliza eventos del core para realizar ciertos procesos, y sólo se dispararán durante la ejecución del cron.

Desinstalación
==============

Cuando se desinstale el plugin, **serán eliminadas todas las tablas de base de datos que se crearon durante su instalación, así cómo todo el contenido que exista dentro de la carpeta del repositorio “editions”.**

No se podrá recuperar ninguno de los datos eliminados.
