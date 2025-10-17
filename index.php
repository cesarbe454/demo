<?php session_start(); ?>



<?php include 'header.php'; // Asume que contiene los metadatos y enlaces a CSS ?>



<body class="hold-transition login-page">



<div class="login-box">



    <div class="login-logo">

        <p id="date"></p>

        <p id="time" class="bold"></p>

    </div>



    <div class="login-box-body">

        <h4 class="login-box-msg"> Ingrese su # de Identificación</h4>



        <form id="attendance" style="display:none;">

            <div class="form-group">

                <label for="office_id">Sede Detectada:</label>

                <select class="form-control" name="office_id" id="office_id" required disabled>

                    <option value="">-- Buscando Sede --</option>

                    <option value="sede_Chicala">Sede Chicala</option>

                    <option value="sede_Pola">Sede Pola</option>

                    <option value="sede_Centro">Sede Centro </option>

                    <option value="sede_Lerida">Sede Lérida </option>

                </select>

            </div>

           

            <div class="form-group">

                <select class="form-control" name="status">

                    <option value="in">Hora de Entrada</option>

                    <option value="out">Hora de Salida</option>

                </select>

            </div>

            <div class="form-group has-feedback">

                <input type="text" class="form-control input-lg" id="employee" name="employee" placeholder="Ingrese su # de Identificación" required>

                <span class="glyphicon glyphicon-calendar form-control-feedback"></span>

            </div>

            <input type="hidden" name="latitude" id="latitude">

            <input type="hidden" name="longitude" id="longitude">

           

            <div class="row">

                <div class="col-xs-12">

                    <button type="submit" class="btn btn-primary btn-block btn-flat" name="signin" id="submit-btn" disabled>

                        <i class="fa fa-sign-in"></i> Registrar

                    </button>

                </div>

            </div>

        </form>

    </div>

   

    <div class="alert alert-success alert-dismissible mt20 text-center" id="registration-alert" style="display:none;">

        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>

        <span class="result"><i class="icon fa fa-check"></i> <span class="message"></span></span>

    </div>

   

    <div class="alert alert-info alert-dismissible mt20 text-center" id="loading-alert" style="display:block;">

        <i class="icon fa fa-spinner fa-spin"></i> Obteniendo ubicación en tiempo real...

    </div>

   

    <div class="alert alert-success mt20 text-center" id="success-alert" style="display:none;">

        <span class="result"><i class="icon fa fa-map-marker"></i> <span class="message"></span></span>

    </div>

   

    <div class="alert alert-danger alert-dismissible mt20 text-center" id="danger-alert" style="display:none;">

        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>

        <span class="result"><i class="icon fa fa-warning"></i> <span class="message"></span></span>

    </div>

       

</div>



<?php include 'scripts.php' // Asume que incluye jQuery, Moment.js, y JS de Bootstrap/AdminLTE ?>



<script type="text/javascript">



// Función para calcular la distancia entre dos coordenadas (Fórmula Haversine)

function getDistance(lat1, lon1, lat2, lon2) {

    var R = 6371; // Radio de la Tierra en km

    var dLat = (lat2 - lat1) * (Math.PI / 180);

    var dLon = (lon2 - lon1) * (Math.PI / 180);

    var a =

        Math.sin(dLat / 2) * Math.sin(dLat / 2) +

        Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);

    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    var d = R * c; // Distancia en km

    return d;

}



$(function() {

    // ✅ Configurar Localización a Español (Requiere moment-with-locales.js)

    moment.locale('es');



    // 1. **CONFIGURACIÓN DE SEDES Y COORDENADAS** 🏢

    const LOCATIONS = {

        'sede_Chicala': { lat: 4.447939, lon: -75.186170, name: 'Chicala' },    

        'sede_Pola': { lat: 4.448367, lon: -75.246986, name: 'Pola' },    

        'sede_Centro': { lat: 4.444367, lon: -75.237306, name: 'Centro' },

        'sede_Lerida': { lat: 4.855237, lon: -74.917024, name: 'Lérida' }

       

    };

    const RADIO_PERMITIDO_KM = 0.1; // 100 metros



    let currentLat = null;

    let currentLon = null;

    let gpsObtained = false;

    let watchId = null;

   

    // 🔑 **NUEVA VARIABLE:** Para rastrear la última acción exitosa del usuario

    let lastActionStatus = null; // Puede ser 'in', 'out', o null



    // 2. Mostrar Hora y Fecha

    var interval = setInterval(function() {

        var momentNow = moment();

        // La fecha ahora saldrá en español (ej. JUE - Octubre 16, 2025)

        $('#date').html(momentNow.format('ddd').toUpperCase() + ' - ' + momentNow.format('MMMM DD, YYYY'));

        $('#time').html(momentNow.format('hh:mm:ss A'));

    }, 100);



    // Función principal de validación de zona

    function validateLocation(lat, lon) {

       

        if (!gpsObtained) {

            $('#danger-alert').show();

            $('#danger-alert .message').html(`Esperando ubicación GPS...`);

            $('#submit-btn').prop('disabled', true);

            $('#office_id').val('').prop('disabled', true);

            return;

        }



        $('#success-alert, #danger-alert').hide();



        let nearestOfficeKey = null;

        let minDistance = Infinity;

        let officeFound = false;



        // **LÓGICA: Encontrar la Sede más cercana y dentro del radio**

        for (const key in LOCATIONS) {

            const office = LOCATIONS[key];

            const distance = getDistance(lat, lon, office.lat, office.lon);



            if (distance < minDistance) {

                minDistance = distance;

                nearestOfficeKey = key;

            }



            if (distance <= RADIO_PERMITIDO_KM) {

                nearestOfficeKey = key;

                officeFound = true;

                break;

            }

        }

       

        const selectedOfficeKey = nearestOfficeKey;



        // **3. Actualizar el selector y el estado**

        if (officeFound) {

            const office = LOCATIONS[selectedOfficeKey];

            const distance_m = (minDistance * 1000).toFixed(2);

           

            // ÉXITO: Establecer Sede AUTOMÁTICAMENTE

            $('#office_id').val(selectedOfficeKey).prop('disabled', true);

            $('#attendance').show();

            // Re-habilita el botón *a menos* que la lógica de formulario lo deshabilite

            $('#submit-btn').prop('disabled', false);

           

            $('#success-alert').show();

            $('#success-alert .message').html(`✅ **Sede ${office.name}** detectada. Distancia: ${distance_m}m.`);

           

        } else {

            // FALLO: Ubicación incorrecta

            let officeName = nearestOfficeKey ? LOCATIONS[nearestOfficeKey].name : 'N/A';

            let distance_m = nearestOfficeKey ? (minDistance * 1000).toFixed(2) : 'N/A';

           

            $('#registration-alert').hide();



            $('#office_id').val('').prop('disabled', true);

            $('#attendance').show();

            $('#submit-btn').prop('disabled', true);

           

            $('#danger-alert').show();

            $('#danger-alert .message').html(`❌ **Ubicación Incorrecta.** No hay sede dentro de ${RADIO_PERMITIDO_KM * 1000}m. Más cercana (${officeName}): ${distance_m}m.`);

        }

    }



    // Función de ÉXITO de la geolocalización

    function geoSuccess(position) {

        currentLat = position.coords.latitude;

        currentLon = position.coords.longitude;

        gpsObtained = true;

        $('#latitude').val(currentLat);

        $('#longitude').val(currentLon);

        $('#loading-alert').hide();

        $('#attendance').show();

        validateLocation(currentLat, currentLon);

    }



    function geoError(error) {

        $('#loading-alert').hide();

        $('#registration-alert').hide();

        $('#success-alert').hide();

        $('#danger-alert').show();

        let errorMessage = 'Error al obtener GPS: ';

       

        switch(error.code) {

            case error.PERMISSION_DENIED:

                errorMessage += "Permiso denegado por el usuario."; break;

            case error.POSITION_UNAVAILABLE:

                errorMessage += "Información de ubicación no disponible."; break;

            case error.TIMEOUT:

                errorMessage += "La solicitud de ubicación ha caducado. Recargue la página."; break;

            default:

                errorMessage += "Error desconocido."; break;

        }



        $('#danger-alert .message').html(errorMessage + ' No se puede validar la zona.');

        $('#submit-btn').prop('disabled', true);

        $('#office_id').prop('disabled', true);

    }

   

    // 3. Iniciar Vigilancia de Ubicación (Tiempo Real)

    if (navigator.geolocation) {

        watchId = navigator.geolocation.watchPosition(geoSuccess, geoError, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });

    } else {

        $('#loading-alert').hide();

        $('#danger-alert').show();

        $('#danger-alert .message').html('Error: Su navegador no soporta Geolocalización.');

        $('#submit-btn').prop('disabled', true);

        $('#office_id').prop('disabled', true);

    }















    // 5. Manejo del Formulario (Lógica para guardar la sede)

    $('#attendance').submit(function(e){

        e.preventDefault();

       

        validateLocation(currentLat, currentLon);

        if ($('#submit-btn').prop('disabled')) {

            return;

        }

       

        const selectedStatus = $('select[name="status"]').val();

       

        // =========================================================

        // 🚨 NUEVA LÓGICA DE ADVERTENCIA POR JORNADA COMPLETA 🚨

        // =========================================================

        if (lastActionStatus === 'out' && selectedStatus === 'in') {

             // Si el último registro fue 'out' (Salida) y se está intentando registrar 'in' (Entrada)

             // Y el campo de ID está vacío (sugiere un intento de nuevo registro inmediatamente)

             if ($('#employee').val().trim() === '') {

                 $('#registration-alert').hide();

                 $('#success-alert').hide();

                 $('#danger-alert').show();

                 $('#danger-alert .message').html('⚠️ **Jornada Completa.** Ya ha registrado su **Hora de Entrada** y **Hora de Salida** para el día. Recargue la página para iniciar una nueva jornada o use otro ID.');

                 

                 // Volver a habilitar el botón y el campo ID sin enviar

                 $('#submit-btn').prop('disabled', false).html('<i class="fa fa-sign-in"></i> Registrar');

                 $('#office_id').prop('disabled', true);

                 return; // Detener el envío del formulario

             }

        }

        // =========================================================

       

        // ✅ CLAVE: Habilitar la sede antes de serializar

        $('#office_id').prop('disabled', false);



        var attendance = $(this).serialize();

       

        // Re-deshabilitar el campo inmediatamente después y mostrar el spinner

        $('#office_id').prop('disabled', true);

        $('#submit-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');



        $.ajax({

            type: 'POST',

            url: 'attendance.php',

            data: attendance, // Incluye employee, status, latitude, longitude, y **office_id**

            dataType: 'json',

            success: function(response){

                $('#submit-btn').prop('disabled', false).html('<i class="fa fa-sign-in"></i> Registrar');

               

                $('#danger-alert').hide();

               

                if(response.error){

                    $('#registration-alert').hide();

                    $('#danger-alert').show();

                    $('#danger-alert .message').html(response.message);

                    validateLocation(currentLat, currentLon);

                }

                else{

                    // ✅ ÉXITO: Actualizar lastActionStatus y mostrar mensaje

                   

                    // Asumimos que la acción exitosa fue la opuesta a 'next_status'

                    // Si next_status es 'out', la acción actual fue 'in'.

                    // Si next_status es 'in', la acción actual fue 'out'.

                    lastActionStatus = response.next_status === 'out' ? 'in' : 'out';



                    $('#registration-alert').show();

                    $('#registration-alert .message').html(response.message);

                    $('#employee').val('');

                   

                    // =========================================================

                    // 🔑 Lógica para cambiar Entrada/Salida

                    // =========================================================

                    if (response.next_status && response.next_status === 'out') {

                        // Si se registró ENTRADA y el backend pide el siguiente estado 'out', solo se muestra la opción de Salida

                        $('select[name="status"]').html('<option value="out">Hora de Salida</option>');

                    } else if (response.next_status && response.next_status === 'in') {

                        // Si se registró SALIDA o es un estado inicial, se muestran ambas (Entrada por defecto)

                        $('select[name="status"]').html('<option value="in">Hora de Entrada</option><option value="out">Hora de Salida</option>');

                    } else {

                        // Comportamiento por defecto si no se recibe next_status

                        $('select[name="status"]').html('<option value="in">Hora de Entrada</option><option value="out">Hora de Salida</option>');

                    }

                   

                    validateLocation(currentLat, currentLon);

                }

            },

            error: function() {

                $('#submit-btn').prop('disabled', false).html('<i class="fa fa-sign-in"></i> Registrar');

               

                $('#registration-alert').hide();

               

                $('#danger-alert').show();

                $('#danger-alert .message').html('Error de conexión con el servidor. Inténtelo de nuevo.');

                validateLocation(currentLat, currentLon);

            }

        });

    });

});

</script>



</body>

</html> 