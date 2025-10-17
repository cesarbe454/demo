<?php
// attendance_export.php

// Incluir el archivo de sesión y de conexión a la base de datos
include 'includes/session.php'; // Si es necesario
include 'includes/conn.php';   // Reemplaza con el nombre de tu archivo de conexión

// 1. Cabeceras para forzar la descarga del archivo CSV/Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Asistencia_' . date('Ymd_His') . '.csv');

// 2. Abrir el flujo de salida
$output = fopen('php://output', 'w');

// 3. Escribir los encabezados de la tabla
// Asegúrate de que los encabezados coincidan con las columnas que extraes
fputcsv($output, array('ID Asistencia', 'Fecha', 'ID Empleado', 'Nombre', 'Apellido', 'Hora Entrada', 'Hora Salida', 'Sede', 'Status', 'Tarde (min)'));

// 4. Consultar los datos
$sql = "SELECT attendance.id as attid, attendance.date, employees.employee_id AS empid, employees.firstname, employees.lastname, attendance.time_in, attendance.time_out, attendance.Sede, attendance.status, attendance.late_time
        FROM attendance
        LEFT JOIN employees ON employees.id = attendance.employee_id
        ORDER BY attendance.date DESC, attendance.time_in DESC";

$query = $conn->query($sql);

// 5. Escribir los datos de las filas
while($row = $query->fetch_assoc()){
    // Formatear la fecha y horas para el archivo Excel
    $date_formatted = date('Y-m-d', strtotime($row['date']));
    $time_in_formatted = date('H:i:s', strtotime($row['time_in']));
    $time_out_formatted = date('H:i:s', strtotime($row['time_out']));
    $status_text = ($row['status']) ? 'A Tiempo' : 'Tarde';

    // Crear un array con los valores de la fila
    $export_row = array(
        $row['attid'],
        $date_formatted,
        $row['empid'],
        $row['firstname'],
        $row['lastname'],
        $time_in_formatted,
        $time_out_formatted,
        $row['Sede'],
        $status_text,
        $row['late_time'] // Asumiendo que tienes esta columna para el tiempo de retraso
    );

    // Escribir la fila en el CSV
    fputcsv($output, $export_row);
}

// 6. Cerrar el flujo de salida
fclose($output);
exit;

?>