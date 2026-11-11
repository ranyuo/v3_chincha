# Portal IESTP Chincha (Laravel)

Portal institucional e intranet del IESTP «Chincha» sobre **Laravel 13**.

Esta versión conserva todo lo que hacía la versión anterior en PHP sin framework (v2_chincha): el mismo contenido, las mismas tablas `pisco_*` de MySQL, la misma aplicación del navegador y las mismas reglas de permisos, numeración y validación. Lo que cambió es la base técnica: rutas, sesiones, protección CSRF, vistas, configuración y tareas programadas ahora son de Laravel.

## Estructura

| Carpeta | Contenido |
|---|---|
| `public/` | Única carpeta pública: `index.php` de Laravel, CSS, JavaScript, imágenes y bibliotecas del navegador. |
| `app/Http/Controllers/` | Página del portal, API JSON (`/api/...`), instalación, cuentas, roles, auditoría y respaldos. |
| `app/Http/Middleware/` | Sesión del portal, cabeceras de seguridad y bloqueo con contraseña temporal. |
| `app/Exceptions/PortalErrors.php` | Mensajes de error del portal (JSON para la API, página sencilla para el resto). |
| `portal/` | Núcleo del portal: reglas de negocio, permisos, mesa de partes, académico, correo, auditoría y respaldos. Composer lo carga solo (`classmap`). |
| `portal/resources/` | `schema.sql`, contenido inicial (`data/portal.json`) y contratos de datos. |
| `resources/views/` | Plantillas Blade. |
| `routes/web.php` | Todas las direcciones del sitio y de la API. |
| `routes/console.php` | Comandos `portal:correo` y `portal:respaldos` y su programación. |
| `config/portal.php` | Ajustes del portal, leídos del `.env`. |
| `storage/app/portal/` | Datos privados que escribe el portal: respaldos, ajustes SMTP cifrados y bloqueo de instalación. No se guarda en Git. |

### Direcciones

| Antes | Ahora |
|---|---|
| `/index.php?route=api/...` | `/api/...` |
| `/install.php` | `/instalar` |
| `/accounts.php` | `/cuentas` |
| `/roles.php` | `/roles` |
| `/auditoria.php` | `/auditoria` |
| `/respaldos.php`, `/backup.php` | `/respaldos`, `/respaldos/sql` |
| `/mesa-de-partes.php`, `/libro-de-reclamaciones.php`, `/bolsa-laboral.php`, `/certificados.php` | `/mesa-de-partes`, `/libro-de-reclamaciones`, `/bolsa-laboral`, `/certificados` |
| `portal-private/mail-worker.php`, `portal-private/cron.php` | `php artisan portal:correo`, `php artisan portal:respaldos` (los ejecuta `php artisan schedule:run`) |

Las direcciones antiguas con `.php` redirigen (301) a las nuevas, así que los enlaces ya publicados siguen funcionando.

## Requisitos del hosting

- **PHP 8.3 o superior** (Laravel 13 lo exige; en hPanel › *Avanzado › Configuración de PHP*) con `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `zlib`, `ctype`, `tokenizer`, `xml` y `session`.
- **Composer** para instalar las dependencias. Hostinger lo trae disponible por SSH en los planes Premium y Business.
- **MariaDB 10.4+ o MySQL 8.** Una base de datos por sitio: las tablas usan el prefijo fijo `pisco_`.
- El sitio debe estar en la **raíz de un dominio o subdominio**, y esa raíz debe ser la carpeta `public/`.
- Permiso de escritura en `storage/` y `bootstrap/cache/`, y en `public/assets/cache/` (755).

## Instalación nueva en Hostinger

1. **Base de datos:** en hPanel › *Bases de datos › Bases de datos MySQL*, crea una base y un usuario con su contraseña. Anota los tres datos.
2. **Código:** entra por SSH (hPanel › *Avanzado › Acceso SSH*) y clona el repositorio **junto a** `public_html`, no dentro:

   ```bash
   cd ~/domains/tu-dominio.edu.pe
   git clone https://github.com/ranyuo/v3_chincha.git portal
   cd portal
   composer install --no-dev --optimize-autoloader
   ```

3. **Raíz pública:** haz que el dominio sirva la carpeta `public/` del proyecto. Lo más simple es reemplazar `public_html` por un enlace:

   ```bash
   cd ~/domains/tu-dominio.edu.pe
   mv public_html public_html.anterior
   ln -s portal/public public_html
   ```

   Si tu plan no permite enlaces, sube el proyecto completo dentro de `public_html`: el `.htaccess` de la raíz envía todas las peticiones a `public/` y deja inaccesibles `.env`, `vendor/`, `storage/` y el resto de carpetas.

4. **Configura:** copia `.env.example` como `.env` y completa:
   - `APP_URL` con la dirección del sitio;
   - `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`;
   - `PORTAL_INSTALL_KEY`: una clave privada aleatoria de 32 caracteres o más. Guárdala.

   Luego genera la clave de la aplicación y optimiza:

   ```bash
   php artisan key:generate
   php artisan optimize
   ```

5. **Instala:** abre `https://tu-dominio/instalar`, escribe la `PORTAL_INSTALL_KEY` y crea la cuenta del administrador principal. Después de instalar, `/instalar` responde «El portal ya está instalado» (código 410). Mientras el portal no esté instalado, la portada muestra un aviso que indica abrir `/instalar`.
6. **Tarea programada (una sola):** en hPanel › *Avanzado › Trabajos cron*, crea una tarea **cada minuto** con:

   ```bash
   php /home/<usuario>/domains/<dominio>/portal/artisan schedule:run
   ```

   Laravel ejecuta desde ahí el envío de correo (`portal:correo`, cada minuto) y los respaldos programados (`portal:respaldos`, cada hora según el horario de la intranet). Ambas tareas depuran la auditoría una vez al día.
7. **Comprueba:**
   - Abre el sitio y entra a la intranet con la cuenta creada.
   - Revisa *Configuración*: datos institucionales y logo.
   - En *Mesa de partes › Áreas receptoras › Editar áreas*, carga las áreas reales con sus correos.
   - En *Usuarios y roles*, crea las cuentas del personal.

**Correo de notificaciones (recomendado):** en la intranet, *Configuración › Configurar correo notificador (SMTP)*, indica el servidor, la cuenta y su contraseña de aplicación y activa el envío. Las credenciales se guardan cifradas con la `PORTAL_INSTALL_KEY` en `storage/app/portal/.smtp-settings.json`. Como alternativa, se pueden definir con las variables `PORTAL_SMTP_*` del `.env`.

**Retención de la auditoría:** se conserva 24 meses por omisión. Para cambiarlo, define `PORTAL_AUDIT_RETENTION_MONTHS` en el `.env` (mínimo 6; `0` la conserva sin límite).

**Sesiones:** duran 30 minutos de inactividad (`SESSION_LIFETIME`) y la cookie `PISCOSESSID` solo viaja por HTTPS (`SESSION_SECURE_COOKIE=true`). Para pruebas locales por HTTP usa `SESSION_SECURE_COOKIE=false`.

## Pasar desde la versión anterior (sin Laravel)

La base de datos **no cambia**: Laravel usa las mismas tablas `pisco_*`, así que el contenido, las cuentas y los archivos se conservan.

1. Respalda los archivos y la base en hPanel.
2. Instala esta versión como en los pasos 2 a 4 de la instalación nueva, con los datos de la **misma base** en el `.env` y la **misma clave** `install_key` del `config.php` anterior como `PORTAL_INSTALL_KEY` (sin ella no se pueden leer los ajustes SMTP guardados).
3. Copia los datos privados de `portal-private/` a `storage/app/portal/`:
   - la carpeta `backups/`;
   - el archivo `.smtp-settings.json`, si existe.
4. Reemplaza los dos cron anteriores (`mail-worker.php` y `cron.php`) por la única tarea `php artisan schedule:run` del paso 6.
5. No abras `/instalar`: el portal ya está instalado y lo indica.

## Actualizar esta versión

```bash
cd ~/domains/tu-dominio.edu.pe/portal
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize
```

La base de datos se actualiza sola la primera vez que se abre el sitio con la versión nueva; no se pierden datos ni cuentas. Lo publicado se sigue editando desde la intranet.

## Dónde se edita

- **CSS y JavaScript del portal:** `public/assets/app/` (`portal.css` y `portal-1.js` a `portal-4.js`) y `public/*.js`.
- **Armazón de la página:** `resources/views/portal.blade.php`.
- **Contenido inicial** que carga la instalación: `portal/resources/data/portal.json`.
- **Imágenes:** `public/assets/chincha/`. Las imágenes JPG o PNG grandes tienen una copia `.webp` que el servidor entrega automáticamente a los navegadores compatibles.
- **Reglas del portal** (validación, permisos, numeración, correo, respaldos): `portal/`.

## Cambios de presentación y contenido institucional

- El logo conserva sus proporciones y permanece fijo. El reemplazo por el archivo original aprobado sigue pendiente; se conserva el archivo existente.
- Se retiró la suscripción al boletín del pie de página. Los datos históricos no se eliminan.
- En **Páginas institucionales** y **Programas de estudio**, el editor de galería permite agregar hasta 12 fotografías adicionales, describirlas, ordenarlas y retirarlas. La imagen principal se muestra primero; las galerías se recorren manualmente y permiten ampliar las imágenes. En los programas, una sola galería acompaña Perfil de egreso y Campo laboral. Si un programa antiguo aún no tiene galería, se aprovecha su brochure cuando es una imagen; no se repiten archivos para simular más fotografías.
- Las páginas institucionales compatibles usan fotografías institucionales ya incluidas como presentación inicial; al guardar una galería propia, esa selección prevalece. Una galería vacía conserva únicamente la imagen principal.
- **Reconocimientos** muestra la foto propia que ya admite cada registro. **Plana jerárquica** reúne los cargos y las coordinaciones en una ficha por persona.
- **Organización institucional** muestra el organigrama estructural del Manual de Perfil del Puesto, versión 2024, página 7 del PDF conservado en el repositorio. La imagen se puede ampliar y se enlaza su fuente. Para actualizarlo, carga un organigrama aprobado en la página correspondiente. Como alternativa, registra las dependencias oficiales mediante **Plana jerárquica → Depende jerárquicamente de**; el portal dibuja sus conexiones y rechaza ciclos o referencias inexistentes.
- Los servicios no se anuncian como disponibles por el solo hecho de estar publicados. Confirma su puesta en funcionamiento en **Configuración → Servicios cuya puesta en funcionamiento está confirmada**; para servicios complementarios, marca además **Servicio implementado y disponible para atención** en cada registro. Esto controla su presentación pública, sin borrar registros ni sustituir los permisos de los módulos internos. El correo receptor del Libro de Reclamaciones es obligatorio al habilitar ese servicio; mientras esté deshabilitado, su ausencia no impide guardar otras opciones.
- **Bolsa laboral** abre una plataforma externa. Se conserva el enlace `bolsa-chincha` existente hasta recibir el enlace confirmado de MINEDU, que puede registrarse en **Configuración → Enlace de Bolsa laboral proporcionado por MINEDU**.

### Diseños, carrusel de programas y galería de noticias

Incorporados desde `ranyuo/v2_flv`.

- **Apariencia del portal:** en la intranet, *Apariencia del portal* (botón «Apariencia»), en tres pasos: **1. Estructura de la página** (Clásico institucional, Moderno, Editorial, Impacto, Campus, Horizonte o Universitario; solo cambia la forma), **2. Colores y letras** (color institucional, de botones y enlaces y destacado; **todas las estructuras usan estos colores**) y **3. Opciones avanzadas** (colores propios de una sola estructura; normalmente no hace falta). La antigua variante «Impacto Chincha» se unió a *Impacto*: al actualizar, el servidor la convierte sola y conserva sus colores avanzados. Con una combinación oscura, Impacto, Campus, Horizonte y Editorial muestran el sitio público con tonos claros porque no tienen versión oscura. Si *Texto del logotipo generado* está vacío, junto al logotipo se muestra el nombre corto oficial completo. El carrusel de portada de Chincha conserva su presentación propia: imagen completa, texto debajo y sin avance automático.
- **Programas de estudio en carrusel:** se muestran en carrusel por omisión, en la portada y en la página de programas. Para cambiar a cuadrícula, usa *Vista en la web* en el módulo *Programas de estudio* o *Presentación de los programas de estudio* en *Portada › Bloques, accesos y cifras*.
- **Noticias con varias fotos:** cada noticia admite la imagen principal y hasta 4 fotos adicionales (5 en total). En la web se muestran como galería con visor ampliado.
- **Accesos rápidos:** la instalación nueva incluye *Plataforma Virtual* (`ext:aula`) y *Correo Institucional* (`ext:correo`). Solo aparecen cuando el enlace tiene dirección y el servicio está confirmado en *Configuración*. Subir los archivos no cambia los datos ya guardados: en una instalación existente, agrega los dos accesos en *Portada › Bloques, accesos y cifras*.
- **Distrito y región** (*Configuración*): se usan en el pie de la intranet, en los títulos emitidos y en los datos para buscadores.

### Módulos y correo

- **Servicios con enlace propio:** `/mesa-de-partes`, `/libro-de-reclamaciones`, `/bolsa-laboral` y `/certificados` abren cada servicio sin la navegación del portal. Siguen sujetos a la confirmación de servicios de *Configuración*.
- **Notificaciones por correo (SMTP):** se configuran en *Configuración*, sección *Configurar correo notificador (SMTP)*. Los mensajes se encolan en `pisco_mail`; el primer intento se hace al terminar la respuesta y los reintentos los hace `php artisan portal:correo`.
- **Mesa de partes:** correlativo anual de expedientes y notificación a la mesa de partes y al solicitante. El correo receptor de *Mesa de partes* debe ser válido. La instalación nueva usa `direcciongeneral@iestpchincha.edu.pe`; en una instalación existente, revísalo en la configuración de *Mesa de partes* antes de habilitar el servicio.

## Pruebas

Pruebas de Laravel (rutas, redirecciones, errores y cabeceras; no necesitan MySQL):

```bash
php artisan test
```

Reglas del contenido (sin base de datos):

```bash
php tests/Integration/observaciones.php
```

Las suites de instalación, integridad, permisos, respaldos, correo y volumen necesitan MariaDB y se describen en `tests/Integration/README.md`. `bash tests/Integration/run.sh` las ejecuta con Docker (después de `composer install`) y el flujo `.github/workflows/qa.yml` las corre en cada push con PHP 8.3 y 8.4.

Las comprobaciones de navegador (`tests/Integration/*.cjs`) requieren Node, Playwright y Chromium. Ejecútalas únicamente contra una **instalación local de pruebas**, nunca contra producción. Los detalles están en `tests/Integration/README.md`.

## Documentación

Los manuales de `docs/manuales/2026-11-10/` y `documentacion/Chincha/` describen el uso del portal, que no cambia. Sus pasos de instalación corresponden a la versión anterior sin Laravel; para instalar esta versión sigue este README.
