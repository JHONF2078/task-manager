# task-manager

![PHP Version](https://img.shields.io/badge/PHP-8.3-%23777BB4?logo=php)
![Symfony](https://img.shields.io/badge/Symfony-7.2-black?logo=symfony)
![Vue](https://img.shields.io/badge/Vue.js-3.x-%234FC08D?logo=vuedotjs)
![License](https://img.shields.io/badge/Status-Dev-green)

Aplicación para la gestión de tareas y usuarios.

---
## 🧭 Índice
- [📄 Descripción](#-descripción)
- [🛠️ Detalles técnicos](#️-detalles-técnicos)
  - [🏗️ Arquitectura general](#-arquitectura-general)
  - [🔐 Autenticación y sesión](#-autenticación-y-sesión)
  - [🔑 Gestión de contraseñas](#-gestión-de-contraseñas)
  - [🗃️ Modelo de datos principal](#-modelo-de-datos-principal)
  - [⚙️ Rendimiento y optimización](#-rendimiento-y-optimización)
  - [🧪 Estándares y calidad](#-estándares-y-calidad)
- [🧩 Aplicación](#-aplicación)
  - [🏠 Vista principal](#-vista-principal)
  - [🧱 UX y componentes](#-ux-y-componentes)
  - [📘 Endpoints destacados (REST)](#-endpoints-destacados-rest)
- [🔄 Interacción entre Backend y Frontend](#-interacción-entre-backend-y-frontend)
- [🖼️ Capturas de pantalla de la aplicación](#-capturas-de-pantalla-de-la-aplicación)
  - [🔐 1. Autenticación (Auth)](#-1-autenticación-auth)
  - [👥 2. Gestión de usuarios](#-2-gestión-de-usuarios)
  - [🗂️ 3. Gestión de tareas](#-3-gestión-de-tareas)
- [✨ Funcionalidades especiales](#-funcionalidades-especiales)
  - [📬 Entorno de pruebas de correo](#-entorno-de-pruebas-de-correo)
  - [👑 Usuario administrador inicial](#-usuario-administrador-inicial)
- [🔧 Configuración de entorno (variables locales no versionadas)](#-configuración-de-entorno-variables-locales-no-versionadas)

---
## 📄 Descripción
`task-manager` permite crear, listar, actualizar y eliminar tareas, así como administrar usuarios relacionados.

## ⚡ Instalación y configuración rápida

### 1. Instalación de dependencias

#### Symfony (PHP)
```cmd
composer install
```

#### NPM (Frontend)
```cmd
npm install
```

### 2. Configuración de Symfony

1. Copia el archivo de entorno:
   ```cmd
   copy .env .env.local
   ```
2. Edita los archivos de entorno según corresponda:
   - **Base de datos:**
     Edita el archivo `.env` y configura:
     ```env
     DATABASE_URL="mysql://root@127.0.0.1:3306/task_db?serverVersion=8.0.32&charset=utf8mb4"
     ```
   - **Email (Mailtrap):**
     Edita el archivo `.env.local` y configura:
     ```env
     MAILTRAP_HOST=sandbox.smtp.mailtrap.io
     MAILTRAP_PORT=2525
     MAILTRAP_USER=TU_USUARIO_MAILTRAP
     MAILTRAP_PASS=TU_CONTRASENA_MAILTRAP
     MAIL_FROM_ADDRESS=no-reply@miapp.local
     MAILER_DSN="smtp://${MAILTRAP_USER}:${MAILTRAP_PASS}@${MAILTRAP_HOST}:${MAILTRAP_PORT}"
     ```
   - **JWT:**
     Las claves ya están generadas en `config/jwt/`. Si necesitas regenerarlas:
     ```cmd
     php bin/console lexik:jwt:generate-keypair
     ```
3. Ejecuta las migraciones para crear la base de datos y ejecutar las migraciones:
   ```cmd
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

### 3. Usuario administrador por defecto

Ejecutar el comado para crear el  administrador con:
 ```cmd
  php bin/console app:create-admin
 ```

- **Email:** `admin@miapp.com`
- **Contraseña:** `admin123`

> **Nota:** El archivo `.env` contiene la configuración de la base de datos y variables generales.

### 4. Arranca el server 

Ejecutar el siguiente comando para arrancar la aplicacio en la url http://127.0.0.1:8000:
 ```cmd
   symfony serve
 ```


---

## 🛠️ Detalles técnicos
### 🏗️ Arquitectura general
- Monolito Symfony 7.2 (backend PHP 8.3)  + Vue 3 (Composition API y con Vuetify para UI) embebido.
- Comunicación: API REST JSON sobre HTTP (endpoints /api/*).
- Estado global en frontend: Pinia + composables (auth, tasks, users).
- Seguridad: JWT (access token) + Refresh Token (almacenado en BD) + CSRF para mutaciones.
- Validación: Symfony Validator en Entidades y DTOs + reglas mínimas en frontend.
- Persistencia: Doctrine ORM sobre MySQL 8.4.3 con migraciones.
- Estándares: PSR-12 (apoyado por PHP-CS-Fixer), tipado estricto (declare(strict_types=1) en PHP), uso de DTOs para separar capa HTTP de dominio.
- posibilidad de trazar EXPLAIN mediante endpoint dedicado.

### 🔐 Autenticación y sesión
- Login devuelve JWT de corta duración (access token) + refresh token persistido (hash) en tabla refresh_token.
- Renovación silenciosa vía endpoint /api/auth/token/refresh (usa cookie HttpOnly / contexto seguro si se configura) y actualización del estado Pinia.
- Logout invalida refresh token (revoca registro) y limpia storage local.
- Protección CSRF activada para mutaciones no públicas; token obtenido desde /api/csrf y enviado en cabecera X-CSRF-Token.

### 🔑 Gestión de contraseñas
- Hash con password_hash() (algoritmo auto / bcrypt/argon según PHP) centralizado en PasswordHasherService.
- Flujo de recuperación: generación de reset token con TTL configurable, envío de email, validación de token único y consumo al usarlo.

### 🗃️ Modelo de datos principal
- User: email único, roles (json), estado activo, timestamps, campos auxiliares de recuperación.
- Task: título, descripción, estado, prioridad, due_date, asignado a (FK usuario), etiquetas (tags/categories json).
- RefreshToken: user_id, token_hash, expiración, metadata de auditoría (revoked_at, last_used_at, replaced_by).
- messenger_messages: almacenamiento transporte Doctrine para tareas asíncronas futuras.

![bd.png](docs/images/others/bd.png)

### ⚙️ Rendimiento y optimización
- Debounce & hashing en filtros de tareas para evitar solicitudes redundantes.
- Endpoint /api/tasks/explain para analizar planes de ejecución (EXPLAIN / ANALYZE adaptable según motor).

EXPLAIN ejemplo:
```markdown

http://localhost:8000/api/tasks/explain?q=test&status=en_progreso&analyze=true

```

### 🧪 Estándares y calidad
- PSR-12 verificado con PHP CS Fixer (reglas comunes + formateo uniforme).
- Uso de atributos para Constraints (Symfony 6+/7 style) y rutas (#[] attributes).
- Código de servicios orientado a inyección de dependencias (constructor) y segregación de responsabilidades (AuthService, PasswordResetService, etc.).

PSR-12 comandos útiles:
```markdown
# Ver violaciones (sin modificar archivos)
composer run cs:check

# Arreglar automáticamente
composer run cs:fix
```

---

## ✨ Informacion Importante

### 📬 Entorno de pruebas de correo

Actualmente, los correos generados por la aplicación (recuperación de contraseña y reportes) se envían a Mailtrap, 
un entorno seguro para pruebas de email. Esto permite verificar el contenido y formato de los correos sin riesgo de enviarlos
a usuarios reales durante el desarrollo.

Para revisar los correos recibidos, accede a tu bandeja de Mailtrap y selecciona el inbox configurado para este proyecto.

- la configuracion SMTP en `.env.local` está preparada para usar Mailtrap.

### 👑 Usuario administrador inicial

La aplicación crea automáticamente un usuario administrador inicial con el correo `admin@miapp.com` y la contraseña `admin123` (encriptada), mediante un Command interno de Symfony. Este proceso no requiere que ejecutes manualmente el comando en consola: el usuario se genera por el propio código cuando es necesario, asegurando que siempre exista un acceso administrativo desde el primer inicio.

---

## 🧩 Aplicación

### 🏠 Vista principal
![Layout / Home](docs/images/general/layout.png)

Layout principal:
- Barra lateral fija con accesos: Home, Usuarios, Tareas y Logout.
- Panel central con bloques iniciales (listados) sobre tareas y navegación rápida.
- UI basada en Vuetify: botones, chips y tipografía consistente.
- Diseño responsive: la barra se colapsa en pantallas pequeñas.

### 🧱 UX y componentes
- Componentes atómicos (formularios de auth, listas, diálogos modales para CRUD de tareas y confirmaciones).
- Uso de Vuetify para consistencia visual y accesibilidad básica (chips, dialogs, forms, alerts).
- Separación de lógica de datos en composables (useTasks, useAuth, useUsers) para independencia UI.

### 📘 Endpoints destacados (REST)
La documentación completa y navegable de la API se expone mediante Swagger UI (OpenAPI):

- Documentación interactiva (Swagger UI): `http://localhost:8000/api/docs`
- Esquema OpenAPI (JSON): `http://localhost:8000/api/docs.json`

Desde esa interfaz puedes:
- Probar endpoints directamente (autenticándote con el JWT en el botón Authorize).
- Ver parámetros, modelos de entrada/salida y códigos de respuesta.

## 🔄 Interacción entre Backend y Frontend

El backend (Symfony) expone una API RESTful que gestiona la autenticación, usuarios y tareas. El frontend (Vue 3) consume estos servicios mediante peticiones HTTP usando composables y servicios personalizados:

- **Servicios (assets/services/):** Encapsulan la lógica de comunicación con la API (autenticación, tareas, usuarios, CSRF, etc.), facilitando el uso y reutilización en los componentes.
- **Composables (assets/composables/):** Proveen funciones reactivas para manejar el estado y lógica de negocio (useAuth, useTasks, useUsers), integrando los servicios y facilitando la composición en los componentes Vue.
- **Pinia (assets/stores/):** Se utiliza para la gestión global del estado de la aplicación (usuarios, autenticación, tareas), permitiendo compartir datos entre componentes de forma eficiente y reactiva.
- **Componentes y Vistas (assets/views/, assets/components/):** Los formularios y vistas consumen los composables y stores para interactuar con el backend, mostrar datos y gestionar acciones del usuario.

El flujo típico es:
1. El usuario interactúa con un formulario o vista.
2. El componente llama a un composable, que usa un servicio para enviar la petición al backend.
3. El backend responde y el composable actualiza el estado global mediante Pinia.
4. Los componentes se actualizan automáticamente mostrando los cambios.

Esta arquitectura permite una integración eficiente, escalable y mantenible entre frontend y backend, aprovechando las capacidades modernas de Vue 3 y Symfony.

### 🖼️ Capturas de pantalla de la aplicación

A continuación se muestran ejemplos visuales de las principales funcionalidades de la aplicación.

### 🔐 1. Autenticación (Auth)
- Inicio de sesión
- Registro de usuario
- Recuperación de contraseña

Capturas:

**Inicio de sesión**
![Login](docs/images/auth/login.png)

**Registro de usuario**
![Registro](docs/images/auth/register.png)

**Recuperacion de Contraseña**
![recovery_send.png](docs/images/auth/recovery_send.png)

**Email en Mailtrap**
![mailtrap.png](docs/images/auth/mailtrap.png)

**Cambio de Contraseña**
![change_password.png](docs/images/auth/change_password.png)

### 👥 2. Gestión de usuarios
Descripción general: administración y visualización de los usuarios.

#### 👑 2.1 Rol Admin
Permisos:
- ver listado de usuarios.
- Buscar por correo.
- Crear nuevos usuarios.
- Editar y desactivar usuarios existentes.

Capturas:

**Listado de usuarios**  
![admin_list.png](docs/images/users/admin_list.png)


#### 👤 2.2 Rol User
Permisos:
- Ver listado de usuarios.
- Ver detalle de usuario.
- Buscar por correo.
- No puede crear, editar ni desactivar a otros usuarios.

Capturas:

**Listado de usuarios**  
![user_list.png](docs/images/users/user_list.png)


### 🗂️ 3. Gestión de tareas
Listado, filtrado, ordenación y detalle de tareas. Incluye exportación y controles de estado / prioridad.

#### 👑 3.1 Rol Admin
Permisos:
- Ver listado de tareas.
- Buscar, Filtrar y Ordenar.
- Crear tareas.
- Editar cualquier tarea, cambiar asignación, estado, prioridad y fechas..
- Eliminar tareas.
- Ver y exportar reporte en csv o pdf.


Capturas:

**Listado de tareas**  
![admin_tasks.png](docs/images/tasks/admin_tasks.png)


#### 👤 3.2 Rol User
Permisos:
- Ver listado de tareas.
- Buscar, Filtrar y Ordenar.
- Ver detalle de tarea.
- Exportar reporte en csv o pdf.
- No puede crear, editar ni eliminar (los botones se ocultan si no es Admin).

Capturas: 
![user_task.png](docs/images/tasks/user_task.png)


#### 👤 3.3 Exportar reporte de tareas

Lista de características de la exportación:
- Formatos disponibles: CSV y PDF.
- El contenido del reporte se genera usando exactamente los filtros activos en la lista (búsqueda, estado, prioridad, asignado, rango de fechas, etc.).
- Reporte diario automático (por comando):
Puedes generar un reporte de rango de fechas y enviarlo por correo (si no se pasa correo se genera localmente en /var/reports).:
    ```bash
    php bin/console app:tasks:daily-report --from=2025-09-01 --to=2025-11-30 --email=jhonzzz@gmail.com
    ```

Capturas reporte de tareas en el front:

***Filtro por  Prioridad = Alta***

![filtro_prioridad.png](docs/images/tasks/filtro_prioridad.png)

***Generar Reporte***

![generar_reporte.png](docs/images/tasks/generar_reporte.png)

***Reporte generado***

![reporte-pdf.png](docs/images/tasks/reporte-pdf.png)
