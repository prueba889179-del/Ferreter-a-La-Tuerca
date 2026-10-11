# Portafolio y Memoria Técnica para el Caso Práctico: Desarrollo de Aplicación Web

---

## 1. Portada

```
========================================================================================
                          CASO PRÁCTICO: DESARROLLO DE APLICACIÓN WEB
========================================================================================

                 APLICACIÓN LEGALTECH: "ABOGADOS & SOCIOS"
           SISTEMA DE GESTIÓN INTEGRAL DE CONTRATOS, FACTURACIÓN Y CLIENTES

                       Alumno: Benjamin
                       Módulo / Asignatura: Desarrollo de Aplicaciones Web
                       Stack Tecnológico: PHP 8.2 + MySQL / MariaDB (DBeaver / Railway)
                       Fecha de Entrega: Octubre 2026

========================================================================================
```

---

## 2. Índice

1. **Portada**
2. **Índice**
3. **Introducción**
4. **Desarrollo de Proyecto o Actividad**
   - **Parte A:** Identificar Componentes y Módulos
   - **Parte B:** Elementos Obligatorios (Cabecera, Pie de página, Roles y Módulos CRUD)
   - **Parte C:** Tecnologías Utilizadas (PHP, MySQL, DBeaver, Railway)
   - **Parte D:** Desarrollo y Codificación (Estructura de Directorios y Código)
   - **Parte E:** Complementos Multimedia (Audio ambiental, Video institucional, Animaciones)
   - **Parte F:** Recursos Usados (Herramientas, Servidor y Extensiones)
   - **Parte G:** Elementos de Seguridad del Proyecto Web (CSRF, XSS, Hashing, PDO)
   - **Parte H:** Despliegue de App Web en Hosting Gratuito (Railway Cloud)
5. **Conclusión**
6. **Bibliografías**

---

## 3. Introducción

El presente proyecto consiste en el desarrollo integral de la aplicación web corporativa **"Abogados & Socios"**, una solución LegalTech concebida para modernizar la gestión contractual y contable de estudios jurídicos contemporáneos. 

En la práctica jurídica diaria, la gestión manual de expedientes, acuerdos mercantiles y cobro de honorarios genera cuellos de botella, inconsistencias fiscales y riesgos de incumplimiento de plazos. Para solventar esta problemática, se implementó un sistema basado en arquitectura cliente-servidor con **PHP 8**, persistencia relacional en **MySQL** (administrado localmente mediante **DBeaver** y XAMPP, y preparado para despliegue en la nube mediante **Railway**).

La plataforma satisface cada uno de los lineamientos del Caso Práctico: control de acceso RBAC (*Role-Based Access Control*) con perfiles diferenciados (**Administrador** y **Abogado**), módulos de **Contratos**, **Facturación / Honorarios**, **Directorio de Clientes** y **Gestión de Usuarios**, complementados con recursos multimedia interactivos y estrictos estándares de seguridad informática.

---

## 4. Desarrollo de Proyecto o Actividad

### Parte A: Identificar Componentes y Módulos

| Módulo | Descripción | Componentes Principales |
| :--- | :--- | :--- |
| **Login (Acceso)** | Control de acceso seguro para mínimo 2 tipos de usuarios (**Administrador** y **Abogado/Empleado**). | - Formulario de credenciales con token CSRF.<br>- Mensajes de validación reactivos.<br>- Selector de cuentas de demostración.<br>- Cifrado seguro de credenciales con `password_verify`. |
| **Inicio (Home / Dashboard)** | Portal central con visión ejecutiva del estudio legal. | - Banner principal con diseño Navy & Gold y tipografía Cinzel.<br>- Métricas en vivo (Contratos vigentes, honorarios cobrados, clientes).<br>- Reproductor multimedia de inducción legal.<br>- Botonera de acceso rápido a módulos. |
| **Módulo de Contratos (Principal)** | Gestión del ciclo de vida contractual y blindaje jurídico. | - Formulario modal para redacción y alta de contratos.<br>- Selector de expediente, tipo, cliente, letrado a cargo y cláusulas.<br>- Filtro en tiempo real por búsqueda de texto y estado.<br>- Edición de términos y eliminación controlada. |
| **Módulo de Facturación** | Control contable de aranceles, cobros e impuestos. | - Emisión de facturas ligadas a contratos existentes.<br>- Cálculo automatizado de IVA (19%) en JavaScript/PHP.<br>- Cambio de estado de pago (Pendiente / Pagado / Anulada).<br>- Reporte contable tabulado. |
| **Módulo de Clientes** | Directorio y expediente de personas jurídicas y naturales. | - Registro de empresas, RUT/identificación y contacto.<br>- Métricas de contratos suscritos por cliente. |
| **Gestión de Usuarios** | Gobernanza de identidades (Solo Administrador). | - Alta de letrados con especialidad jurídica.<br>- Asignación de privilegios RBAC.<br>- Protección contra auto-eliminación de la cuenta en sesión. |

---

### Parte B: Elementos Obligatorios

1. **Cabecera (Header):**
   - **Logotipo:** Icono de balanza de la justicia (`⚖️ Abogados & Socios - Estudio Jurídico / LegalTech`).
   - **Menú de navegación:** Enlaces directos a *Inicio*, *Contratos*, *Facturación*, *Clientes* y *Usuarios* (visible según privilegios).
   - **Indicador de Usuario y Rol:** Insignia visual indicando el nombre y etiqueta de rol (*Admin* dorada / *Abogado* azul), con botón de cierre de sesión (`logout.php`). Visible en todas las páginas mediante `includes/header.php`.

2. **Pie de Página (Footer):**
   - Información del desarrollador: **Benjamin**.
   - Nombre de la aplicación: **Abogados & Socios LegalTech**.
   - Año de desarrollo dinámico: **2026**.
   - Reproductor interactivo de audio ambiental legal.

3. **Login y Control de Roles:**
   - **Administrador (`admin@abogados.com` / `admin123`):** Acceso total al sistema, creación de letrados, auditoría de honorarios y edición de cualquier expediente.
   - **Abogado (`abogado@abogados.com` / `admin123`):** Acceso operativo a gestión de contratos y consulta de clientes/facturación. El módulo de administración de usuarios queda bloqueado por la directiva `require_admin()`.

4. **Lógica de Negocio y Reglas de Validación:**
   - Al emitir una factura, el sistema extrae automáticamente el monto pactado del contrato y computa el 19% de IVA.
   - Búsqueda dinámica instantánea por texto y filtrado por estado (`Activo`, `Borrador`, `En Revisión`, `Finalizado`).
   - Integridad referencial con claves foráneas (`ON DELETE RESTRICT` / `CASCADE`).

---

### Parte C: Tecnologías Utilizadas

- **Front-end:**
  - **HTML5:** Marcado semántico (`<header>`, `<main>`, `<nav>`, `<section>`, `<footer>`).
  - **Vanilla CSS3:** Paleta cromática Navy & Gold (`#0a0f1d`, `#d4af37`), Glassmorphism con `backdrop-filter`, diseño responsivo flexbox/grid y microinteracciones.
  - **JavaScript (ES6):** Filtrado reactivo en tablas de datos sin recargar la página, cálculo dinámico de impuestos en facturación y controladores del reproductor multimedia.
- **Back-end:**
  - **PHP 8.2:** Programación estructurada y modular con sesiones nativas, manejo de excepciones PDO y separación de componentes.
- **Base de Datos:**
  - **MySQL / MariaDB:** Modelo relacional normalizado con tablas `usuarios`, `clientes`, `contratos` y `facturas`.
  - **DBeaver:** Gestor gráfico universal utilizado para la administración, ejecución del script SQL y verificación de índices y llaves foráneas.
- **Infraestructura Cloud:**
  - **Railway:** Plataforma PaaS seleccionada para el despliegue productivo tanto del servicio PHP como de la base de datos MySQL en la nube.

---

### Parte D: Desarrollo y Codificación

#### Organización del Proyecto (Estructura de Directorios):

```
c:\xampp\htdocs\Benjamin\
├── index.php                  # Dashboard principal y resumen ejecutivo
├── login.php                  # Autenticación segura de 2 roles (Admin / Abogado)
├── logout.php                 # Destrucción segura de sesiones
├── contratos.php              # Módulo Principal: CRUD y ciclo de vida de contratos
├── facturacion.php            # Módulo de Facturación: Emisión de recibos y cálculo IVA
├── clientes.php               # Módulo de Directorio de Clientes y Empresas
├── usuarios.php               # Módulo Administrativo: Gestión de roles y letrados
├── Procfile                   # Definición de proceso web para Railway Cloud
├── css/
│   └── styles.css             # Estilos CSS de alta gama (Navy, Gold, Glassmorphism)
├── js/
│   └── scripts.js            # Filtros en vivo, modals y cálculo automático
├── includes/
│   ├── auth.php               # Funciones de seguridad, tokens CSRF, XSS y RBAC
│   ├── header.php             # Cabecera global compartida
│   └── footer.php             # Pie de página institucional y reproductor acústico
├── db/
│   ├── conexion.php           # Conexión PDO híbrida (Local XAMPP / DBeaver / Railway)
│   └── datos.sql              # Script SQL DDL + DML con datos de prueba
└── media/
    ├── images/                # Activos gráficos
    └── audio/                 # Activos sonoros
```

---

### Parte E: Complementos Multimedia

1. **Audio Ambiental de Despacho Legal:**
   - Incorporación de pista acústica instrumental en el pie de página (`<audio id="ambientAudio">`) que puede ser activada/desactivada interactivamente por el usuario para ambientar la lectura de cláusulas complejas.
2. **Video Institucional:**
   - Enlace e integración de video en formato iframe responsive en la página de inicio, exponiendo el rol de las herramientas LegalTech en el análisis de contratos corporativos.
3. **Animaciones e Interacciones CSS:**
   - Transiciones suaves en tarjetas de métricas (`transform: translateY(-3px)`), efectos de brillo dorado en botones y transiciones de apertura para ventanas modales.

---

### Parte F: Recursos Usados

- **Editor / IDE:** Visual Studio Code / Antigravity IDE.
- **Servidor Local:** XAMPP (Apache 2.4, PHP 8.2).
- **Gestor de Base de Datos:** **DBeaver Community**, conectado a MySQL local (`localhost:3306`) y a Railway Cloud vía credenciales remotas.
- **Navegador Web:** Google Chrome / Microsoft Edge para pruebas de interfaz y auditoría responsiva.
- **Librerías de Fuentes:** Google Fonts (*Cinzel* y *Plus Jakarta Sans*).

---

### Parte G: Elementos de Seguridad del Proyecto Web

1. **Protección contra CSRF (Cross-Site Request Forgery):**
   - Generación de token criptográficamente seguro con `random_bytes(32)` almacenado en sesión y validado en todas las peticiones `POST` mediante `verify_csrf()`.
2. **Mitigación de XSS (Cross-Site Scripting):**
   - Sanitización de cada salida visual mediante la función `e($string)` que encapsula `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
3. **Prevención de Inyección SQL:**
   - Implementación estricta de **Sentencias Preparadas con PDO** (`$stmt->prepare()` y enlaces `:parametro`), impidiendo la concatenación directa de entradas de usuario.
4. **Almacenamiento Criptográfico de Contraseñas:**
   - Empleo de la función estándar `password_hash()` con algoritmo BCRYPT para la creación de contraseñas y `password_verify()` en el inicio de sesión.
5. **Control de Acceso Basado en Roles (RBAC):**
   - Verificación de sesión mediante `require_login()` y restricción exclusiva al perfil administrativo mediante `require_admin()`.

---

### Parte H: Despliegue de App Web en Hosting Gratuito (Railway)

La aplicación cuenta con compatibilidad para despliegue en la plataforma **Railway**:

1. **Variables de Entorno Soportadas en `conexion.php`:**
   - `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
2. **Archivo `Procfile` de Inicio:**
   - `web: php -S 0.0.0.0:$PORT`
3. **Importación en DBeaver:**
   - En DBeaver, se crea una nueva conexión seleccionando el driver **MySQL**, ingresando el *Host*, *Puerto* y credenciales entregadas por el plugin MySQL de Railway. Luego se ejecuta el archivo `db/datos.sql`.
4. **URL de Despliegue:**
   - `https://abogados-socios-production.up.railway.app` *(o el dominio generado al vincular el repositorio en Railway)*.

---

## 5. Conclusión

El desarrollo de la aplicación web **Abogados & Socios** permitió consolidar conocimientos esenciales en el ciclo completo de ingeniería de software para la web. Se integró exitosamente una arquitectura backend limpia en **PHP 8** con persistencia en **MySQL**, cumpliendo con la exigencia de modelar la lógica de negocio del sector legal: contratos con estados evolutivos y facturación con cálculos impositivos automáticos.

Asimismo, el uso de herramientas profesionales de la industria como **DBeaver** para la administración de bases de datos y la preparación para **Railway** garantizan que la solución trascienda el ámbito de un ejercicio local, exhibiendo un estándar de seguridad, estética y calidad listo para entornos reales.

---

## 6. Bibliografías

1. **The PHP Group.** (2024). *PHP: Manual de PHP - PDO y Preparación de Sentencias*. Recuperado de https://www.php.net/manual/es/book.pdo.php
2. **Oracle Corporation.** (2024). *MySQL 8.0 Reference Manual*. Recuperado de https://dev.mysql.com/doc/
3. **DBeaver Corp.** (2024). *DBeaver Community Documentation: Connecting to MySQL Databases*. Recuperado de https://dbeaver.io/docs/
4. **Railway App Inc.** (2024). *Deploying PHP and MySQL Applications on Railway*. Recuperado de https://docs.railway.app/
5. **OWASP Foundation.** (2023). *OWASP Top Ten: Cross-Site Scripting (XSS) and Cross-Site Request Forgery (CSRF) Prevention Cheat Sheet*. Recuperado de https://cheatsheetseries.owasp.org/
