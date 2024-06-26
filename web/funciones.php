<?php
session_start();
require_once 'dbcon.php';
// Directorio donde se guardará el archivo
$uploadDir = 'logs/';
if(isset($_GET['terminal'])) {

    $terminal = $_GET['terminal'];

    $conexion = conexionBD();

    $sql = "SELECT nombreTerminal, codTerminal FROM terminal WHERE codTerminal = '$terminal'";

    $result = mysqli_query($conexion, $sql);

    while ($row = mysqli_fetch_assoc($result)) {

        $term = $row['nombreTerminal'];
        $codterm = $row['codTerminal'];

    }

}

// Verificar si se ha subido algún archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_SESSION['usuario'])) {
    // Verificar si se subió un archivo
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Ruta completa del archivo en el servidor
        $uploadDir = 'logs/'.$codterm.'_'.$term.'/'; // Ajusta esta ruta según tu configuración
        $uploadFile = $uploadDir . $_SESSION['usuario'] . "___" . basename($_FILES['file']['name']);

        // Verificar si el archivo es un ZIP
        $fileType = mime_content_type($_FILES['file']['tmp_name']);
        if ($fileType === 'application/zip') {
            // Intentar mover el archivo subido al directorio deseado
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
                // Crear un nuevo objeto ZipArchive
                $zip = new ZipArchive;
                if ($zip->open($uploadFile) === TRUE) {
                    // Descomprimir el contenido del archivo ZIP en la misma ubicación
                    $zip->extractTo($uploadDir);

                    // Cerrar el archivo ZIP
                    $zip->close();

                    // Renombrar archivos descomprimidos
                    renameNewFolders($uploadDir, $_SESSION['usuario'] . "___");

                    // Eliminar el archivo ZIP original
                    unlink($uploadFile);

                    // Archivo descomprimido correctamente
                    http_response_code(200);
                } else {
                    // Error al abrir el archivo ZIP
                    http_response_code(500);
                }
            } else {
                // Error al mover el archivo subido
                http_response_code(500);
                
            }
        } else {
            // El archivo subido no es un archivo ZIP
            http_response_code(400);
        }
    } else {
        // Error al subir el archivo
        http_response_code(500);
        
    }
} else {
    // No se ha subido ningún archivo o la solicitud no es POST
    http_response_code(400);
}

// Función para renombrar solo las carpetas recién descomprimidas
function renameNewFolders($dir, $prefix) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                // Verificar si la carpeta ha sido modificada recientemente (creada durante la extracción del ZIP)
                $lastModifiedTime = filemtime($filePath);
                if ($lastModifiedTime >= filemtime($dir)) {
                    // Renombrar la carpeta
                    $newFolderName = $prefix . $file;
                    rename($filePath, $dir . '/' . $newFolderName);
                }
            }
        }
    }
}

// Procesar formulario de inicio de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['loginSubmit'])) {
    // Recoger los datos del formulario según las id de los campos input de estos
    $loginEmail = $_POST["loginEmail"];
    $loginPassword = $_POST["loginPassword"];

    login(null, $loginEmail, $loginPassword);
    
    // Aquí puedes realizar la validación y autenticación del usuario
}

// Procesar formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registroSubmit'])) {
    // Recoger los datos del formulario según su id en el input
    $registroNombre = $_POST["registroNombre"];
    $registroEmail = $_POST["registroEmail"];
    $registroPassword = $_POST["registroPassword"];

    login($registroNombre, $registroEmail, $registroPassword);
}








?>