<?php
/**
 * Pomoćna funkcija za renderovanje (prikazivanje) view-a unutar glavnog layout-a.
 */
function render_view($view, $data = []) {
    extract($data);
    $view_file_path = '../app/views/' . $view . '.php';
    if (file_exists($view_file_path)) {
        require_once '../app/views/layouts/main.php';
    } else {
        die("Greška: View fajl nije pronađen na putanji: " . $view_file_path);
    }
}