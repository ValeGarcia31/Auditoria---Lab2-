$(document).on("submit", "#restablecer_form", function(event){
    event.preventDefault();

    var pass = $("#usu_pass").val();
    var confirmacion = $("#usu_pass_confir").val();

    if (pass.length < 8 || pass.length > 72 || pass !== confirmacion) {
        Swal.fire({
            title: "Restablecer contraseña",
            text: "La contraseña debe tener entre 8 y 72 caracteres y coincidir con la confirmación.",
            icon: "error",
            confirmButtonColor: "#5156be",
        });
        return;
    }

    $.post("../../controller/usuario.php?op=restablecer", $(this).serialize(), function(datos){
        if (datos === "1") {
            Swal.fire({
                title: "Restablecer contraseña",
                text: "Contraseña actualizada. Ya puedes iniciar sesión.",
                icon: "success",
                confirmButtonColor: "#5156be",
            }).then(function(){
                window.location.href = "../../index.php?m=5";
            });
        } else if (datos === "2") {
            Swal.fire({
                title: "Restablecer contraseña",
                text: "La contraseña no es válida.",
                icon: "error",
                confirmButtonColor: "#5156be",
            });
        } else {
            Swal.fire({
                title: "Restablecer contraseña",
                text: "El enlace no es válido o ya venció. Solicita uno nuevo.",
                icon: "error",
                confirmButtonColor: "#5156be",
            });
        }
    });
});
