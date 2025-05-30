<?php
// Enable Error Display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Rome');

// Set internal encoding to UTF-8
mb_internal_encoding('UTF-8');

// Function to handle multibyte string padding (since PHP doesn't have mb_str_pad)
if(!function_exists('mb_str_pad')){
function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
    $input_len = mb_strlen($input);
    $pad_len = mb_strlen($pad_string);

    if ($input_len >= $pad_length) {
        return $input;
    }

    $need_pad_chars = $pad_length - $input_len;

    $pad_count = ceil($need_pad_chars / $pad_len);
    $padded = str_repeat($pad_string, $pad_count);

    if ($pad_type === STR_PAD_LEFT) {
        return mb_substr($padded, 0, $need_pad_chars) . $input;
    } elseif ($pad_type === STR_PAD_BOTH) {
        $left_pad = floor($need_pad_chars / 2);
        $right_pad = ceil($need_pad_chars / 2);
        return mb_substr($padded, 0, $left_pad) . $input . mb_substr($padded, 0, $right_pad);
    } else { // STR_PAD_RIGHT
        return $input . mb_substr($padded, 0, $need_pad_chars);
    }
}
}


include "../../includes/config.php";

// Retrieve and validate the date passed via GET, otherwise use today's date
if (isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) {
    $dataStampa = $_GET['data'];
} else {
    $dataStampa = date('Y-m-d');
}

// Query Appointments for the Current Date
$stmt = $dbh->prepare("
    SELECT
        appuntamento.data AS data,
        TIME_FORMAT(appuntamento.oraInizio, '%H:%i') AS oraInizio,
        TIME_FORMAT(appuntamento.oraFine, '%H:%i') AS oraFine,
        aula.nAula AS luogo,
        appuntamento.descrizione AS descrizione,
        progetto.nomeProgetto AS corso
    FROM appuntamento
    LEFT JOIN aula ON aula.idAula = appuntamento.luogo
    LEFT JOIN progetto ON appuntamento.idCorso = progetto.idProgetto
    WHERE data = :data AND isDeleted=0
    ORDER BY appuntamento.oraInizio, aula.nAula
");
$stmt->bindParam(':data', $dataStampa);

// Execute the Query and Check for Any Errors
if (!$stmt->execute()) {
    print_r($stmt->errorInfo());
    exit;
}

// Retrieve the Appointments
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to Create an ASCII Table
function create_ascii_table($appuntamenti) {
    // Configuration Parameters
    $LUOGO_WIDTH = 30;
    $TIME_START_HOUR = 8;
    $TIME_END_HOUR = 19;
    $HOURS_DISPLAYED = $TIME_END_HOUR - $TIME_START_HOUR + 1;
    $MINUTES_PER_CHAR = 5;
    $TOTAL_MINUTES_DISPLAYED = $HOURS_DISPLAYED * 60;
    $TIME_WIDTH = $TOTAL_MINUTES_DISPLAYED / $MINUTES_PER_CHAR;
    $CHARS_PER_HOUR = $TIME_WIDTH / $HOURS_DISPLAYED;
    $OFFSET_CHAR = $CHARS_PER_HOUR / 2;

    $TOTAL_ROW_WIDTH = 1 + $LUOGO_WIDTH + 1 + $TIME_WIDTH + 1;

    $table = "";

    // Top Border
    $table .= "+" . str_repeat("-", $LUOGO_WIDTH) . "+" . str_repeat("-", $TIME_WIDTH) . "+\n";

    // Time Ruler
    $table .= "|" . str_repeat(" ", $LUOGO_WIDTH) . "|";
    for ($h = $TIME_START_HOUR; $h <= $TIME_END_HOUR; $h++) {
        $table .= mb_str_pad($h, $CHARS_PER_HOUR, " ", STR_PAD_BOTH);
    }
    $table .= "|\n";

    // Column Label
    $table .= "|" . mb_str_pad(" Luogo ", $LUOGO_WIDTH, " ", STR_PAD_BOTH) . "|";
    $table .= str_repeat(" ", $TIME_WIDTH) . "|\n";

    // Separator
    $table .= "+" . str_repeat("-", $LUOGO_WIDTH) . "+" . str_repeat("-", $TIME_WIDTH) . "+\n";

    // Appointment Rows
    foreach ($appuntamenti as $app) {
        $luogo = $app['luogo'] ?? ''; // Gestione di null con il coalescing operator
        $titolo_raw = (empty($app['descrizione'])) ? $app['corso'] : $app['descrizione'];
        $titolo = $titolo_raw;
        // Parsing Hours and Minutes
        list($start_hour, $start_minute) = explode(':', $app['oraInizio']);
        list($end_hour, $end_minute) = explode(':', $app['oraFine']);

        $start_hour = (int)$start_hour;
        $start_minute = (int)$start_minute;
        $end_hour = (int)$end_hour;
        $end_minute = (int)$end_minute;

        // Calculate Minutes from the Start Time of Display (8:00)
        $start_minutes_from_display_start = ($start_hour - $TIME_START_HOUR) * 60 + $start_minute;
        $end_minutes_from_display_start = ($end_hour - $TIME_START_HOUR) * 60 + $end_minute;

        // Skip Appointments Completely Outside Range
        if ($end_minutes_from_display_start <= 0 || $start_minutes_from_display_start >= $TOTAL_MINUTES_DISPLAYED) {
            continue;
        }

        // Clamp Times to the Displayed Interval
        $effective_start_minutes = max(0, $start_minutes_from_display_start);
        $effective_end_minutes = min($TOTAL_MINUTES_DISPLAYED, $end_minutes_from_display_start);

        // Initial Position and Duration in Characters
        $start_char = intval($effective_start_minutes / $MINUTES_PER_CHAR) + $OFFSET_CHAR;

        $duration_minutes = $effective_end_minutes - $effective_start_minutes;
        $duration_chars = max(0, intval($duration_minutes / $MINUTES_PER_CHAR));

        // Ensure that the Bar Does Not Exceed the Width of the Time Area
        $duration_chars = min($duration_chars, $TIME_WIDTH - $start_char);

        // Row with Appointment Bar
        $table .= "|" . mb_str_pad($luogo, $LUOGO_WIDTH, " ", STR_PAD_RIGHT) . "|";
        $table .= str_repeat(" ", $start_char);
        $table .= str_repeat("=", $duration_chars);
        $table .= str_repeat(" ", $TIME_WIDTH - $start_char - $duration_chars);
        $table .= "|\n";

        // Course Description or Title Row
        $titolo_visualizzato = $titolo;
        if (mb_strlen($titolo_visualizzato) > 26) {
            // Truncate the Title to 23 Characters and Add "..."
            $titolo_visualizzato = mb_substr($titolo_visualizzato, 0, 23) . '...';
        }
        $table .= "|" . mb_str_pad("-> " . $titolo_visualizzato, $LUOGO_WIDTH, " ", STR_PAD_RIGHT) . "|";
        $table .= str_repeat(" ", $TIME_WIDTH);
        $table .= "|\n";
    }

    // Bottom Border
    $table .= "+" . str_repeat("-", $LUOGO_WIDTH) . "+" . str_repeat("-", $TIME_WIDTH) . "+\n";

    return $table;
}

function create_ascii_list_table($appuntamenti) {
    $col_progetto = 30;
    $col_luogo = 30;
    $col_inizio = 30;
    $col_fine = 30;
    $col_descrizione = 30;

    $total_width = 1 + $col_progetto + 1 + $col_luogo + 1 + $col_inizio + 1 + $col_fine + 1 + $col_descrizione + 1;

    $table = "";

    // Top Border
    $table .= "+" . str_repeat("-", $col_progetto) . "+"
             . str_repeat("-", $col_luogo) . "+"
             . str_repeat("-", $col_inizio) . "+"
             . str_repeat("-", $col_fine) . "+"
             . str_repeat("-", $col_descrizione) . "+\n";

    // Headers
    $table .= "|" . mb_str_pad("Progetto", $col_progetto, " ", STR_PAD_BOTH)
             . "|" . mb_str_pad("Luogo", $col_luogo, " ", STR_PAD_BOTH)
             . "|" . mb_str_pad("Inizio", $col_inizio, " ", STR_PAD_BOTH)
             . "|" . mb_str_pad("Fine", $col_fine, " ", STR_PAD_BOTH)
             . "|" . mb_str_pad("Descrizione", $col_descrizione, " ", STR_PAD_BOTH) . "|\n";

    // Header Border
    $table .= "+" . str_repeat("-", $col_progetto) . "+"
             . str_repeat("-", $col_luogo) . "+"
             . str_repeat("-", $col_inizio) . "+"
             . str_repeat("-", $col_fine) . "+"
             . str_repeat("-", $col_descrizione) . "+\n";

    // Data rows
    foreach ($appuntamenti as $app) {
        $desc_visualizzata_nc = $app['descrizione'];
        $desc_visualizzata = $desc_visualizzata_nc;
        if (mb_strlen($desc_visualizzata) > 26) {
            // Truncate the Title to 23 Characters and Add "..."
            $desc_visualizzata = mb_substr($desc_visualizzata, 0, 23) . '...';
        }
        $corso_visualizzato_nc=$app['corso'];
        $corso_visualizzato=$corso_visualizzato_nc;
        if(mb_strlen($corso_visualizzato)>26){
            $corso_visualizzato=mb_substr($corso_visualizzato,0,23) . '...';
        }
        $table .= "|" . mb_str_pad($corso_visualizzato, $col_progetto, " ", STR_PAD_RIGHT)
                 . "|" . mb_str_pad($app['luogo'] ?? '', $col_luogo, " ", STR_PAD_RIGHT)
                 . "|" . str_pad($app['oraInizio'], $col_inizio, " ", STR_PAD_RIGHT)
                 . "|" . str_pad($app['oraFine'], $col_fine, " ", STR_PAD_RIGHT)
                 . "|" . mb_str_pad($desc_visualizzata, $col_descrizione, " ", STR_PAD_RIGHT) . "|\n";
    }

    // Bottom border
    $table .= "+" . str_repeat("-", $col_progetto) . "+"
             . str_repeat("-", $col_luogo) . "+"
             . str_repeat("-", $col_inizio) . "+"
             . str_repeat("-", $col_fine) . "+"
             . str_repeat("-", $col_descrizione) . "+\n";

    return $table;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dettagli Appuntamenti</title>
    <style>
        body {
            font-family: monospace;
            white-space: pre;
            font-size: 12px;
            background-color: #1e1e1e;
            color: #ffffff; 
        }
        th, td {
                border: 1px solid #000 !important;
                color:rgb(110, 110, 110);
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
        .print-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .chiudi-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: rgb(170, 70, 43);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .print-button:hover {
            background-color: #367c39;
        }

        .chiudi-button:hover {
            background-color: rgb(201, 23, 23);
        }

        @media print {
            /* Reset of Light Theme for Printing */
            body {
                background-color: white !important; 
                color: black !important;           
            }

            @page {
                size: landscape;
            }

            .print-button,
            .chiudi-button {
                display: none;
            }

            th, td {
                border: 1px solid #000 !important;
                color: #000 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
        }
    </style>
 
</head>
<body>
    <h1>Prenotazioni del giorno: <?php echo date('d/m/Y', strtotime($dataStampa)); ?></h1>

    <div>
        <pre><?php echo create_ascii_table($appuntamenti); ?></pre>
        <br>
        <pre><?php echo create_ascii_list_table($appuntamenti); ?></pre>
        Documento generato automaticamente il <?php echo date('d-m-Y H:i:s'); ?>
    </div>

    <button class="print-button" onclick="window.print()">Stampa questa scheda</button>
    <button onclick="closeTab()" class="chiudi-button">Chiudi questa scheda</button>
    <script>
        function closeTab() {
            // Attempt to Close the Tab
            window.close();
        }
    </script>
</body>
</html>
