<?php
// Admin menu
add_action('admin_menu', 'register_assistant_form_admin_menu');
function register_assistant_form_admin_menu() {
    add_menu_page(
        'Jaro Form Submissions',
        'Form Submissions',
        'manage_options',
        'assistant-submissions',
        'render_assistant_form_admin_page',
        'dashicons-feedback',
        25
    );
}

// WP_List_Table not needed now, so skipping it for simplicity

// Enqueue DataTables and custom JS
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_assistant-submissions') return;

    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', ['jquery'], null, true);
});

function render_assistant_form_admin_page() {
    global $wpdb;
    $table_name = 'jaro_forms_submissions';
    $form_names = $wpdb->get_col("SELECT DISTINCT form_name FROM {$table_name} WHERE form_name IS NOT NULL");
    $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A);

    echo '<div class="wrap"><h1>Jaro All Submissions</h1>';

    echo '<div style="margin: 20px 0;">';
    echo '<a id="exportCSV" href="' . admin_url('admin.php?page=assistant-submissions&export=csv') . '" class="button button-primary">Export CSV</a>';
   echo '<select id="formNameFilter" style="margin-left:20px;padding:10px;height:50px;">';
echo '<option value="" style="font-weight:bold; color:#333;">Forms Filter</option>';
    foreach ($form_names as $form_name) {
        $formatted_name = ucwords($form_name); 
       echo '<option value="' . esc_attr($formatted_name) . '" style="padding:5px; font-size:14px; color:#000;">' . esc_html($formatted_name) . '</option>';

    }
    echo '</select></div>';

    echo '<table id="jaro-forms-table" class="display" style="width:100%">
        <thead><tr>';

    if (!empty($results)) {
        foreach (array_keys($results[0]) as $header) {
            echo '<th>' . esc_html(ucwords(str_replace('_', ' ', $header))) . '</th>';
        }
        echo '<th>Action</th>';
        echo '</tr></thead><tbody>';

        foreach ($results as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                echo '<td>' . esc_html($value) . '</td>';
            }
            echo '<td><button class="delete-row button" data-id="' . esc_attr($row['id']) . '">Delete</button></td>';
            echo '</tr>';
        }

        echo '</tbody>';
    } else {
        echo '<th>No data found</th></tr></thead><tbody></tbody>';
    }

    echo '</table>';

    // DataTables + Filter + Export + Delete JS
    $export_base_url = admin_url('admin.php?page=assistant-submissions&export=csv');
    echo <<<HTML
    <script>
    jQuery(document).ready(function($) {
        var table = $('#jaro-forms-table').DataTable({
            "scrollX": true,
            "pageLength": 10
        });

        $('#formNameFilter').on('change', function () {
            var selected = $(this).val();
            table.column(1).search(selected).draw();
            var exportUrl = "$export_base_url";
            if (selected) {
                exportUrl += "&form_name=" + encodeURIComponent(selected);
            }
            $('#exportCSV').attr('href', exportUrl);
        });

        // Row deletion
        $('#jaro-forms-table').on('click', '.delete-row', function () {
            if (!confirm('Are you sure you want to delete this row?')) return;

            var rowId = $(this).data('id');
            var button = $(this);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'delete_form_submission',
                    id: rowId
                },
                success: function(response) {
                    if (response.success) {
                        table.row(button.parents('tr')).remove().draw();
                    } else {
                        alert('Failed to delete row.');
                    }
                }
            });
        });
    });
    </script>
    HTML;

    echo '</div>';
}

// CSV Export
add_action('admin_init', 'export_assistant_submissions_to_csv');
function export_assistant_submissions_to_csv() {
    if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'assistant-submissions') return;
    if (!isset($_GET['export']) || $_GET['export'] !== 'csv') return;

    global $wpdb;
    $table_name = 'jaro_forms_submissions';
    $form_name = isset($_GET['form_name']) ? sanitize_text_field($_GET['form_name']) : '';

    $query = "SELECT * FROM {$table_name}";
    if (!empty($form_name)) {
        $query .= $wpdb->prepare(" WHERE form_name = %s", $form_name);
    }
    $query .= " ORDER BY id ASC";

    $results = $wpdb->get_results($query, ARRAY_A);
    if (empty($results)) {
        wp_die('No data found to export.');
    }

    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="form_submissions.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($results[0]));
    foreach ($results as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

// Ajax deletion handler
add_action('wp_ajax_delete_form_submission', 'delete_form_submission_handler');
function delete_form_submission_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['id'])) {
        wp_send_json_error('Missing ID');
    }

    global $wpdb;
    $table_name = 'jaro_forms_submissions';
    $id = intval($_POST['id']);

    $deleted = $wpdb->delete($table_name, ['id' => $id]);

    if ($deleted !== false) {
        wp_send_json_success('Deleted');
    } else {
        wp_send_json_error('Failed');
    }
}
