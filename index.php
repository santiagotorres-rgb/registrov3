<?php
// ============================================================================
// public/index.php  ->  FRONT CONTROLLER (enrutador)
// ----------------------------------------------------------------------------
// Punto de entrada único de toda la aplicación. Ahora, antes de enrutar
// cualquier acción, se inicia la sesión (Auth::iniciar()) porque TODAS las
// rutas —incluida la de login— necesitan que $_SESSION esté disponible.
// Las rutas de autenticación (login, autenticar, logout) son las únicas que
// no exigen sesión previa, ya que su función es precisamente crearla.
// ============================================================================

// Se incluye primero el helper de sesión/roles, y luego los controladores.
require_once __DIR__ . "/../config/Auth.php";
require_once __DIR__ . "/../controllers/AprendizController.php";
require_once __DIR__ . "/../controllers/AuthController.php";
require_once __DIR__ . "/../controllers/DashboardController.php";
require_once __DIR__ . "/../controllers/UsuarioController.php";
require_once __DIR__ . "/../controllers/NotaController.php";

// Se inicia (o retoma) la sesión de PHP para toda la petición actual.
Auth::iniciar();

// Se lee la acción pedida por la URL; por defecto "dashboard" (pantalla de
// inicio distinta según el rol, en vez de mandar a todos al mismo listado).
$accion = $_GET["accion"] ?? "dashboard";

// Se instancian los controladores. Sus constructores ya abren la conexión
// a la base de datos y dejan listo el modelo correspondiente.
$controladorAprendices = new AprendizController();
$controladorAuth       = new AuthController();
$controladorDashboard  = new DashboardController();
$controladorUsuario    = new UsuarioController();
$controladorNota       = new NotaController();

// Switch principal: cada "case" delega en el método adecuado del controlador.
switch ($accion) {

    // -------------------- Rutas de autenticación --------------------
    case "login":
        // Muestra el formulario de inicio de sesión.
        $controladorAuth->mostrarLogin();
        break;

    case "autenticar":
        // Procesa el POST del formulario de login (valida usuario/contraseña).
        $controladorAuth->login();
        break;

    case "logout":
        // Cierra la sesión activa.
        $controladorAuth->logout();
        break;

    // -------------------- Registro público (aprendiz / coordinador) --------------------
    case "registro":
        // Muestra el formulario de auto-registro.
        $controladorAuth->mostrarRegistro();
        break;

    case "registrar":
        // Procesa el POST del formulario de registro.
        $controladorAuth->registrar();
        break;

    // -------------------- Recuperación de contraseña --------------------
    case "recuperar":
        // Paso 1: muestra el formulario donde se pide el correo.
        $controladorAuth->mostrarRecuperar();
        break;

    case "enviar_recuperacion":
        // Paso 2: genera el token y envía el correo con el enlace.
        $controladorAuth->enviarRecuperacion();
        break;

    case "restablecer":
        // Paso 3 (GET, al hacer clic en el enlace) / Paso 4 (POST, al enviar la nueva contraseña).
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $controladorAuth->restablecer();
        } else {
            $controladorAuth->mostrarRestablecer();
        }
        break;

    // -------------------- Dashboard (pantalla de inicio por rol) --------------------
    case "dashboard":
        // Muestra el dashboard correspondiente al rol de la sesión activa.
        $controladorDashboard->mostrarDashboard();
        break;

    // -------------------- Rutas de aprendices (protegidas) --------------------
    case "listar":
        // Muestra la tabla de aprendices (exige sesión iniciada).
        $controladorAprendices->listar();
        break;

    case "crear":
        // Formulario de registro de un aprendiz nuevo (exige rol coordinador).
        $controladorAprendices->mostrarFormularioCrear();
        break;

    case "guardar":
        // Procesa el registro de un nuevo aprendiz (exige rol coordinador).
        $controladorAprendices->guardar();
        break;

    case "editar":
        // Formulario de edición (exige rol docente o coordinador).
        $controladorAprendices->mostrarFormularioEditar();
        break;

    case "actualizar":
        // Procesa la edición de un aprendiz existente.
        $controladorAprendices->actualizar();
        break;

    case "eliminar":
        // Elimina un aprendiz (exige rol coordinador). Borra el registro
        // por completo; para el retiro normal, usar "aprendices_inactivar".
        $controladorAprendices->eliminar();
        break;

    case "aprendices_inactivar":
        // Marca a un aprendiz como retirado, sin borrar su registro.
        $controladorAprendices->inactivar();
        break;

    case "aprendices_activar":
        // Reactiva a un aprendiz previamente marcado como retirado.
        $controladorAprendices->activar();
        break;

    // -------------------- Notas de los aprendices --------------------
    case "notas":
        // Ver/gestionar las notas de un aprendiz (docente/coordinador).
        $controladorNota->gestionar();
        break;

    case "notas_guardar":
        // Procesa el registro de una nueva nota.
        $controladorNota->guardar();
        break;

    case "notas_eliminar":
        // Elimina una nota.
        $controladorNota->eliminar();
        break;

    case "mis_notas":
        // El propio aprendiz consulta sus notas (solo lectura).
        $controladorNota->misNotas();
        break;

    // -------------------- CRUD de usuarios (exclusivo coordinador) --------------------
    case "usuarios":
        // Listado de todas las cuentas del sistema.
        $controladorUsuario->listar();
        break;

    case "usuarios_crear":
        // Formulario para crear una cuenta nueva (aprendiz, docente o coordinador).
        $controladorUsuario->mostrarFormularioCrear();
        break;

    case "usuarios_guardar":
        // Procesa el registro de una nueva cuenta.
        $controladorUsuario->guardar();
        break;

    case "usuarios_editar":
        // Formulario de edición de una cuenta existente.
        $controladorUsuario->mostrarFormularioEditar();
        break;

    case "usuarios_actualizar":
        // Procesa la edición de una cuenta existente.
        $controladorUsuario->actualizar();
        break;

    case "usuarios_eliminar":
        // Elimina una cuenta de usuario.
        $controladorUsuario->eliminar();
        break;

    default:
        // Acción desconocida: se redirige a un lugar seguro según el estado de sesión.
        if (Auth::estaAutenticado()) {
            header("Location: index.php?accion=listar");
        } else {
            header("Location: index.php?accion=login");
        }
        exit;
}
