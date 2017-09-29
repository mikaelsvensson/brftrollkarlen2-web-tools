<?php
$local_file_names = scandir($cfg['reports']['archive_folder'], SCANDIR_SORT_ASCENDING);
$do_sync_action = isset($_POST['action']) && $_POST['action'] == 'sync';
?>
<div class="container-fluid">
    <form action="?report=sync" method="post" class="form">
        <?php
        const HTML_ENTITY_UNICODE_FOLDER = "&#x1f5c1;";
        $drive_service = new Google_Service_Drive($client);

        // Fetch Google Docs and RFT documents, and also folders.
        $remote_files = $drive_service->files->listFiles(array(
            "q" => "mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            "fields" => "files(appProperties,id,name,parents),kind,nextPageToken",
            "pageSize" => 1000
        ))->getFiles();

        // Find out the folder id of the user's Google Drive root.
        $roots = [
            $drive_service->files->get("root", array("fields" => "id"))->id
        ];

        // Sort the list of files (and folders) by name.
        usort($remote_files, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });

        function processSyncFormFolder($parent, $indent)
        {
            global $remote_files, $cfg;
            foreach ($remote_files as $obj) {
                $isFileInCurrentFolder = is_array($obj->parents) && in_array($parent, $obj->parents);
                if ($isFileInCurrentFolder) {
                    printf('<option value="%s" %s>%s%s</option>',
                        $obj->id,
                        $obj->id == $cfg['reports']['default_sync_folder'] ? 'selected="selected"' : '',
                        str_repeat("&nbsp;", $indent * 5),
                        $obj->name);

                    processSyncFormFolder($obj->id, $indent + 1);
                }
            }
        }

        ?>
        <h3>Kopiera rapportfiler till Google Drive</h3>

        <p>Använd den här funktionen för att kopiera alla arkiverade PDF-filer till en mapp på Google Drive.</p>

        <p><?= sprintf('Totalt finns det %d arkiverade rapporter p&aring; den h&auml;r sidan (oklart hur m&aring;nga som redan kopierats till Google Drive).', count($local_file_names)); ?></p>

        <div class="form-group">
            <label>Mapp </label>
            <select name="target_folder" class="form-control">
                <?php
                foreach ($roots as $root) {
                    processSyncFormFolder($root, 0);
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" name="action" value="sync" class="btn btn-primary">Kopiera till Google Drive</button>
        </div>
    </form>

        <div class="<?=$do_sync_action ? 'show' : 'hidden'?>">
            <button type="button" class="btn btn-default btn-xs"
                    onclick="$('#sync-form-result > p:has(span.label-default)').toggleClass('hidden')">
                Visa/dölj filer som redan fanns på Google Drive
            </button>
        </div>

    <div id="sync-form-result">
        <?php
        if ($do_sync_action) {
            $folder_id = $_POST['target_folder'];
            if (!empty($folder_id)) {
                $drive_service = new Google_Service_Drive($client);

                // Fetch Google Docs and RFT documents, and also folders.
                $query = sprintf("'%s' in parents and trashed = false", $folder_id);
//            printf('<p>Query: %s</p>', $query);
                $remote_files = $drive_service->files->listFiles(array(
                    "q" => $query,
                    "fields" => "files(size,id,name,parents),kind,nextPageToken",
                    "pageSize" => 1000
                ))->getFiles();

                // Find out the folder id of the user's Google Drive root.
                $roots = [
                    $drive_service->files->get("root", array("fields" => "id"))->id
                ];

                // Sort the list of files (and folders) by name.
                usort($remote_files, function ($a, $b) {
                    return strcmp($a->name, $b->name);
                });

                $remote_file_names = array_map(function ($item) {
                    return $item->name;
                }, $remote_files);

                $remote_file_sizes = array_combine($remote_file_names, array_map(function ($item) {
                    return $item->size;
                }, $remote_files));

                $files_to_copy = $local_file_names;
//            $files_to_copy = array_diff($local_file_names, $remote_file_names);
//            $files_to_copy = array_splice($files_to_copy, 0, 5);

                $total_file_duration = 1.0;
                $total_file_count = 0;
                $avg_file_duration = 1.0;

                $before_upload_time = microtime(true) - $script_start_time;
                $max_script_execution_time = intval(ini_get('max_execution_time'));
                $max_time = $max_script_execution_time * 0.8;

                $result = array();

                foreach ($files_to_copy as $file_to_copy) {
                    if ($file_to_copy == '.' || $file_to_copy == '..') {
                        continue;
                    }

                    $remote_file_size = isset($remote_file_sizes[$file_to_copy]) ? $remote_file_sizes[$file_to_copy] : -1;
                    $file_path = $cfg['reports']['archive_folder'] . $file_to_copy;
                    $local_file_size = filesize($file_path);

                    if (!in_array($file_to_copy, $remote_file_names) || $remote_file_size != $local_file_size) {
                        $estimated_time = $before_upload_time + $total_file_duration + $avg_file_duration;
                        if ($estimated_time < $max_time) {
                            $start_current_file = microtime(true);

                            $content = file_get_contents($file_path);

                            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                                'name' => $file_to_copy,
                                'mimeType' => 'application/pdf',
                                'parents' => [$folder_id]));
                            $file = $drive_service->files->create($fileMetadata, array(
                                'data' => $content,
                                'mimeType' => 'application/pdf',
                                'uploadType' => 'multipart',
                                'fields' => 'id'));

                            $end_current_file = microtime(true);
                            $current_file_duration = $end_current_file - $start_current_file;
                            $total_file_duration += $current_file_duration;
                            $total_file_count += 1;
                            $avg_file_duration = $total_file_duration / $total_file_count;
                            $result[$file_to_copy] = '<span class="label label-success">Kopierad.</span>';
                        } else {
                            $result[$file_to_copy] = '<span class="label label-warning">Tiden tog slut. F&ouml;rs&ouml;k igen.</span>';
                        }
                    } else {
                        $result[$file_to_copy] = sprintf('<span class="label label-default">Kopierad sedan tidigare.</span>');
                    }
                }

                foreach ($result as $file_name => $status) {
                    printf('<p><span style="display: inline-block; width: 30em">%s:</span> %s</p>', $file_name, $status);
                }
            } else {
                printf('<p>Ojd&aring;! Du valde ingen mapp.</p>');
            }
        }
        ?>
    </div>
</div>
